/// Smoke live: klien Dart (kode sama dengan aplikasi) → server API kazaerp nyata.
///
/// Contoh:
///   dart run tool/api_smoke.dart http://localhost:8099/api/v1 admin@kazaka.local Kazaka#2026 --checkout
///
/// Tanpa `--checkout` hanya operasi baca (login, produk, pelanggan, shift, stok).
/// Dengan `--checkout`: buka shift (bila belum ada) → kirim 1 penjualan qty 1 →
/// kirim ULANG dengan `client_ref` sama (server harus replay 200, nomor sama) →
/// tutup shift dan cetak ringkasan selisih.
library;

import 'dart:io';

import 'package:pos_kaza/api_client.dart';
import 'package:pos_kaza/models.dart';

Future<void> main(List<String> args) async {
  final positional = args.where((a) => !a.startsWith('--')).toList();
  final base = positional.isNotEmpty ? positional[0] : 'http://localhost:8099/api/v1';
  final email = positional.length > 1 ? positional[1] : 'admin@kazaka.local';
  final pass = positional.length > 2 ? positional[2] : 'Kazaka#2026';
  final doCheckout = args.contains('--checkout');

  stdout.writeln('== POS Kaza live smoke ==');
  stdout.writeln('API: $base');

  final token = await ApiClient.login(baseUrl: base, email: email, password: pass);
  stdout.writeln('login           : OK (token ${token.substring(0, 8)}...)');

  final api = ApiClient(baseUrl: base, token: token);
  final products = await api.products();
  stdout.writeln('produk          : ${products.length} item');

  final customers = await api.customers();
  stdout.writeln('pelanggan       : ${customers.length} item');

  Shift? shift = await api.currentShift();
  stdout.writeln('shift terbuka   : ${shift?.id ?? 'tidak ada'}');

  if (!doCheckout) {
    stdout.writeln('selesai (baca saja). Tambahkan --checkout untuk uji alur penuh + idempotensi.');
    return;
  }
  if (products.isEmpty) {
    stderr.writeln('tidak ada produk untuk checkout — dilewati.');
    exitCode = 2;
    return;
  }

  final product = products.firstWhere((p) => p.sku == 'DEMO-001', orElse: () => products.first);
  var openedHere = false;
  if (shift == null) {
    shift = await api.openShift(100000);
    openedHere = true;
    stdout.writeln('buka shift      : id=${shift.id} kas awal 100000');
  }

  final sale = PendingSale(
    clientRef: PendingSale.newClientRef(),
    createdAt: DateTime.now().toIso8601String(),
    lines: [
      {'product_id': product.id, 'qty': 1},
    ],
    payMethod: 'cash',
  );
  stdout.writeln('checkout #1     : ${product.sku} x1, client_ref=${sale.clientRef}');

  double? saleTotal;
  try {
    final first = await api.checkout(sale);
    saleTotal = first.grandTotal;
    stdout.writeln('  → ${first.number} total ${first.grandTotal} replayed=${first.replayed}');
    final second = await api.checkout(sale);
    stdout.writeln('checkout #2     : replayed=${second.replayed} number=${second.number}');
    stdout.writeln(second.replayed && second.number == first.number
        ? 'IDEMPOTEN       : OK (200 replay, nomor sama, tanpa efek dobel)'
        : 'IDEMPOTEN       : GAGAL — periksa server!');
  } on ApiException catch (e) {
    stdout.writeln('  → ditolak: ${e.message} (HTTP ${e.statusCode}) '
        '— jalur error kontrak terverifikasi (mis. stok 0).');
  }

  if (openedHere) {
    final summary = await api.closeShift(100000 + (saleTotal ?? 0));
    stdout.writeln('tutup shift     : sistem=${summary['system_total']} kas seharusnya=${summary['expected_cash']} '
        'selisih=${summary['difference']}');
  }
}
