import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:pos_kaza/api_client.dart';
import 'package:pos_kaza/models.dart';

http.Response jsonRes(Object data, int code) =>
    http.Response(jsonEncode(data), code, headers: {'content-type': 'application/json; charset=utf-8'});

void main() {
  test('login sukses mengembalikan token', () async {
    final mock = MockClient((req) async {
      expect(req.url.path, '/api/v1/login');
      expect(req.url.host, 'server.local');
      return jsonRes({
        'token': 'tok-1',
        'user': {'id': 1},
      }, 200);
    });
    final token = await ApiClient.login(
      baseUrl: 'http://server.local/api/v1',
      email: 'a@b.c',
      password: 'x',
      httpClient: mock,
    );
    expect(token, 'tok-1');
  });

  test('login 401 melempar ApiException non-network', () async {
    final mock = MockClient((req) async => jsonRes({'message': 'Kredensial salah.'}, 401));
    expect(
      () => ApiClient.login(baseUrl: 'http://x/api/v1', email: 'a@b.c', password: 'x', httpClient: mock),
      throwsA(isA<ApiException>()
          .having((e) => e.statusCode, 'statusCode', 401)
          .having((e) => e.isNetwork, 'isNetwork', isFalse)
          .having((e) => e.isClientError, 'isClientError', isTrue)),
    );
  });

  test('checkout mengirim client_ref + lines dan memetakan replay (200)', () async {
    late Map<String, dynamic> sent;
    final mock = MockClient((req) async {
      expect(req.headers['Authorization'], 'Bearer tok');
      sent = Map<String, dynamic>.from(jsonDecode(req.body) as Map);
      return jsonRes({
        'data': {'id': 7, 'number': 'POS-1', 'subtotal': 10000, 'tax': 1100, 'grand_total': 11100, 'replayed': true},
      }, 200);
    });
    final api = ApiClient(baseUrl: 'http://x/api/v1', token: 'tok', httpClient: mock);
    final sale = PendingSale(
      clientRef: 'ref-1',
      createdAt: 'now',
      lines: [
        {'product_id': 1, 'qty': 2},
      ],
      payMethod: 'cash',
      customerId: 5,
    );
    final res = await api.checkout(sale);
    expect(sent['client_ref'], 'ref-1');
    expect(sent['customer_id'], 5);
    expect((sent['lines'] as List).single['product_id'], 1);
    expect(res.replayed, isTrue);
    expect(res.number, 'POS-1');
    expect(res.grandTotal, 11100);
  });

  test('produk memetakan price & tier dari server', () async {
    final mock = MockClient((req) async => jsonRes({
          'data': [
            {'id': 3, 'sku': 'T-001', 'name': 'Teh', 'price': '15000.00', 'tier': 'grosir'},
          ],
        }, 200));
    final api = ApiClient(baseUrl: 'http://x/api/v1', token: 'tok', httpClient: mock);
    final list = await api.products();
    expect(list.single.price, 15000);
    expect(list.single.tier, 'grosir');
  });

  test('shift null saat belum ada shift', () async {
    final mock = MockClient((req) async => jsonRes({'data': null}, 200));
    final api = ApiClient(baseUrl: 'http://x/api/v1', token: 'tok', httpClient: mock);
    expect(await api.currentShift(), isNull);
  });

  test('error jaringan → ApiException.isNetwork', () async {
    final mock = MockClient((req) async => throw http.ClientException('down'));
    final api = ApiClient(baseUrl: 'http://x/api/v1', token: 'tok', httpClient: mock);
    expect(
      () => api.currentShift(),
      throwsA(isA<ApiException>().having((e) => e.isNetwork, 'isNetwork', isTrue)),
    );
  });

  test('respons non-JSON dari server ditolak dengan pesan jelas', () async {
    final mock = MockClient((req) async => http.Response('<html>oops</html>', 500));
    final api = ApiClient(baseUrl: 'http://x/api/v1', token: 'tok', httpClient: mock);
    expect(() => api.products(), throwsA(isA<ApiException>()));
  });
}
