import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:pos_kaza/models.dart';

void main() {
  test('Cart: add, gabung qty, setQty 0 hapus, subtotal', () {
    final p1 = Product(id: 1, sku: 'A', name: 'Produk A', price: 1000, tier: 'retail');
    final p2 = Product(id: 2, sku: 'B', name: 'Produk B', price: 2500.5, tier: 'retail');
    final c = Cart();
    c.add(p1);
    c.add(p1);
    c.add(p2, 3);
    expect(c.lines.length, 2);
    expect(c.qtyTotal, 5);
    expect(c.subtotal, 2 * 1000 + 3 * 2500.5);
    c.setQty(1, 0);
    expect(c.lines.length, 1);
    c.setQty(2, 5);
    expect(c.lines.single.qty, 5);
    c.remove(2);
    expect(c.isEmpty, isTrue);
  });

  test('PendingSale JSON round-trip mempertahankan client_ref & status', () {
    final s = PendingSale(
      clientRef: 'ref-1',
      createdAt: '2026-10-16T08:00:00',
      lines: [
        {'product_id': 1, 'qty': 2},
      ],
      payMethod: 'qris',
      customerId: 9,
      status: 'rejected',
      error: 'Stok tidak cukup.',
      saleNumber: 'POS-1',
    );
    final r = PendingSale.fromJson(jsonDecode(jsonEncode(s.toJson())) as Map<String, dynamic>);
    expect(r.clientRef, 'ref-1');
    expect(r.customerId, 9);
    expect(r.status, 'rejected');
    expect(r.error, 'Stok tidak cukup.');
    expect(r.saleNumber, 'POS-1');
    final payload = r.toCheckoutPayload();
    expect(payload['client_ref'], 'ref-1');
    expect(payload['customer_id'], 9);
    expect((payload['lines'] as List).single['product_id'], 1);
  });

  test('Product.fromJson menerima angka string dari MySQL', () {
    final p = Product.fromJson(const {'id': '3', 'sku': '', 'name': '', 'price': '15000.00', 'tier': 'grosir'});
    expect(p.id, 3);
    expect(p.price, 15000);
    expect(p.tier, 'grosir');
    expect(p.name, '');
  });

  test('SaleResult memetakan replay', () {
    final r = SaleResult.fromJson(const {'id': 7, 'number': 'POS-9', 'subtotal': '1', 'tax': '0', 'grand_total': '1', 'replayed': true});
    expect(r.number, 'POS-9');
    expect(r.replayed, isTrue);
  });
}
