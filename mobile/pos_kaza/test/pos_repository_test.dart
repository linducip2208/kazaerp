import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:pos_kaza/api_client.dart';
import 'package:pos_kaza/local_store.dart';
import 'package:pos_kaza/models.dart';
import 'package:pos_kaza/pos_repository.dart';
import 'package:shared_preferences/shared_preferences.dart';

http.Response jsonRes(Object data, int code) =>
    http.Response(jsonEncode(data), code, headers: {'content-type': 'application/json; charset=utf-8'});

http.Response saleRes(String number, {bool replayed = false, int code = 201}) => jsonRes({
      'data': {'id': 1, 'number': number, 'subtotal': 1, 'tax': 0, 'grand_total': 1, 'replayed': replayed},
    }, code);

PendingSale sale(String ref) => PendingSale(
      clientRef: ref,
      createdAt: 't',
      lines: [
        {'product_id': 1, 'qty': 1},
      ],
      payMethod: 'cash',
    );

Future<LocalStore> makeStore() async {
  SharedPreferences.setMockInitialValues({});
  return LocalStore.load();
}

PosRepository repoFor(LocalStore store, http.Client mock) =>
    PosRepository(api: ApiClient(baseUrl: 'http://x/api/v1', token: 't', httpClient: mock), store: store);

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('client_ref unik dan berformat pos-*', () {
    final a = PosRepository.newClientRef();
    final b = PosRepository.newClientRef();
    expect(a, startsWith('pos-'));
    expect(a, isNot(b));
  });

  test('enqueue persisten lintas instance store', () async {
    final store = await makeStore();
    final repo = repoFor(store, MockClient((req) async => saleRes('POS-1')));
    await repo.enqueue(sale('ref-a'));
    final store2 = await LocalStore.load();
    expect(store2.loadSales().single.clientRef, 'ref-a');
    expect(await repo.pendingCount(), 1);
  });

  test('submit sukses (201) → synced + nomor tersimpan + keluar dari hitungan pending', () async {
    final store = await makeStore();
    late Map<String, dynamic> sent;
    final repo = repoFor(store, MockClient((req) async {
      sent = Map<String, dynamic>.from(jsonDecode(req.body) as Map);
      return saleRes('POS-9');
    }));
    final s = sale('ref-1');
    await repo.enqueue(s);
    expect(await repo.submit(s), 'synced');
    expect(sent['client_ref'], 'ref-1');
    final stored = store.loadSales().single;
    expect(stored.status, 'synced');
    expect(stored.saleNumber, 'POS-9');
    expect(await repo.pendingCount(), 0);
  });

  test('replay 200 (idempoten) dianggap sukses, bukan ditolak', () async {
    final store = await makeStore();
    final repo = repoFor(store, MockClient((req) async => saleRes('POS-9', replayed: true, code: 200)));
    final s = sale('ref-2');
    await repo.enqueue(s);
    expect(await repo.submit(s), 'synced');
    expect(store.loadSales().single.saleNumber, 'POS-9');
  });

  test('jaringan mati → tetap pending; syncPending berhenti & melaporkan antrean utuh', () async {
    final store = await makeStore();
    final repo = repoFor(store, MockClient((req) async => throw http.ClientException('down')));
    await repo.enqueue(sale('ref-1'));
    await repo.enqueue(sale('ref-2'));
    final report = await repo.syncPending();
    expect(report.synced, 0);
    expect(report.abortedOffline, isTrue);
    expect(report.stillPending, 2);
    expect(await repo.pendingCount(), 2);
    expect(store.loadSales().every((s) => s.status == 'pending'), isTrue);
  });

  test('422 → rejected dengan pesan server; retryRejected + sync berikutnya sukses', () async {
    final store = await makeStore();
    var call = 0;
    final repo = repoFor(store, MockClient((req) async {
      call++;
      if (call == 1) return jsonRes({'message': 'Stok tidak cukup.'}, 422);
      return saleRes('POS-10');
    }));
    await repo.enqueue(sale('ref-x'));
    expect(await repo.submit(store.loadSales().single), 'rejected');
    expect(store.loadSales().single.error, 'Stok tidak cukup.');
    await repo.retryRejected();
    expect(store.loadSales().single.status, 'pending');
    final report = await repo.syncPending();
    expect(report.synced, 1);
    expect(report.rejected, 0);
    expect(store.loadSales().single.status, 'synced');
  });

  test('submitNew menyimpan lebih dulu lalu mengirim (tidak ada penjualan hilang)', () async {
    final store = await makeStore();
    final repo = repoFor(store, MockClient((req) async => saleRes('POS-11')));
    final s = repo.buildSale(lines: [
      {'product_id': 2, 'qty': 3},
    ], payMethod: 'qris');
    expect(await repo.submitNew(s), 'synced');
    expect(store.loadSales().single.clientRef, s.clientRef);
  });
}
