/// Orkestrasi POS: antrean penjualan offline + sinkron idempoten ke server.
library;

import 'api_client.dart';
import 'local_store.dart';
import 'models.dart';

class SyncReport {
  SyncReport({this.synced = 0, this.rejected = 0, this.stillPending = 0, this.abortedOffline = false});

  int synced;
  int rejected;
  int stillPending;
  bool abortedOffline;

  String get summary {
    if (abortedOffline) {
      return 'Server tidak terjangkau — $stillPending transaksi tetap di antrean.';
    }
    return '$synced tersinkron, $rejected ditolak, $stillPending menunggu.';
  }
}

class PosRepository {
  PosRepository({required this.api, required this.store});

  final ApiClient api;
  final LocalStore store;

  /// client_ref unik per penjualan (implementasi di models.dart agar bisa dipakai tool pure-Dart).
  static String newClientRef() => PendingSale.newClientRef();

  PendingSale buildSale({required List<Map<String, dynamic>> lines, required String payMethod, int? customerId}) {
    return PendingSale(
      clientRef: newClientRef(),
      createdAt: DateTime.now().toIso8601String(),
      lines: lines,
      payMethod: payMethod,
      customerId: customerId,
    );
  }

  /// Simpan ke antrean lokal SEBELUM dikirim (offline-first: tidak ada penjualan hilang).
  Future<void> enqueue(PendingSale sale) async {
    final list = store.loadSales()..add(sale);
    await store.saveSales(list);
  }

  Future<void> _replace(PendingSale sale) async {
    final list = store.loadSales();
    final i = list.indexWhere((s) => s.clientRef == sale.clientRef);
    if (i >= 0) {
      list[i] = sale;
    } else {
      list.add(sale);
    }
    await store.saveSales(list);
  }

  /// Kirim satu penjualan. Sukses/replay → synced; 4xx → rejected; jaringan → tetap pending.
  /// Mengembalikan status akhir: 'synced' | 'rejected' | 'pending'.
  Future<String> submit(PendingSale sale) async {
    try {
      final res = await api.checkout(sale);
      sale.status = 'synced';
      sale.saleNumber = res.number;
      sale.error = null;
    } on ApiException catch (e) {
      if (e.isNetwork) {
        sale.status = 'pending';
        sale.error = e.message;
      } else {
        sale.status = 'rejected';
        sale.error = e.message;
      }
    }
    await _replace(sale);
    return sale.status;
  }

  /// Simpan ke antrean lalu coba kirim (dipakai tombol Bayar).
  Future<String> submitNew(PendingSale sale) async {
    await enqueue(sale);
    return submit(sale);
  }

  /// Sinkronkan semua yang pending; berhenti lebih awal bila server tidak terjangkau.
  Future<SyncReport> syncPending() async {
    final report = SyncReport();
    for (final sale in store.loadSales().where((s) => s.status == 'pending').toList()) {
      final st = await submit(sale);
      if (st == 'synced') {
        report.synced++;
      } else if (st == 'rejected') {
        report.rejected++;
      } else {
        report.stillPending++;
        report.abortedOffline = true;
        break; // server tidak terjangkau → jangan buang waktu percobaan berikutnya
      }
    }
    final list = store.loadSales();
    report.stillPending = list.where((s) => s.status == 'pending').length;
    report.rejected = list.where((s) => s.status == 'rejected').length;
    return report;
  }

  /// Kembalikan transaksi yang ditolak ke antrean (setelah admin memperbaiki data).
  Future<void> retryRejected() async {
    final list = store.loadSales();
    for (final s in list.where((s) => s.status == 'rejected')) {
      s.status = 'pending';
      s.error = null;
    }
    await store.saveSales(list);
  }

  Future<int> pendingCount() async => store.loadSales().where((s) => s.status == 'pending').length;
}
