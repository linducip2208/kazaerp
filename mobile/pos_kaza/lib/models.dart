/// Model data POS mobile (mirror kontrak API /api/v1 kazaerp).
library;

import 'dart:math';

double asDouble(Object? v) => v == null ? 0 : double.parse(v.toString());

int asInt(Object? v) => v == null ? 0 : (v is int ? v : int.parse(v.toString()));

class Product {
  final int id;
  final String sku;
  final String name;
  final double price;
  final String tier;

  Product({required this.id, required this.sku, required this.name, required this.price, required this.tier});

  factory Product.fromJson(Map<String, dynamic> j) => Product(
        id: asInt(j['id']),
        sku: (j['sku'] ?? '') as String,
        name: (j['name'] ?? '') as String,
        price: asDouble(j['price']),
        tier: (j['tier'] ?? 'retail') as String,
      );

  Map<String, dynamic> toJson() => {'id': id, 'sku': sku, 'name': name, 'price': price, 'tier': tier};
}

class Customer {
  final int id;
  final String code;
  final String name;
  final String tier;
  final String? phone;

  Customer({required this.id, required this.code, required this.name, required this.tier, this.phone});

  factory Customer.fromJson(Map<String, dynamic> j) => Customer(
        id: asInt(j['id']),
        code: (j['code'] ?? '') as String,
        name: (j['name'] ?? '') as String,
        tier: (j['tier'] ?? 'retail') as String,
        phone: j['phone'] as String?,
      );

  Map<String, dynamic> toJson() => {'id': id, 'code': code, 'name': name, 'tier': tier, 'phone': phone};
}

class CartLine {
  final Product product;
  double qty;

  CartLine({required this.product, this.qty = 1});

  double get total => product.price * qty;

  Map<String, dynamic> toCheckoutJson() => {'product_id': product.id, 'qty': qty};
}

/// Keranjang belanja POS (murni, mudah diuji).
class Cart {
  final List<CartLine> lines = [];

  void add(Product p, [double qty = 1]) {
    final i = lines.indexWhere((l) => l.product.id == p.id);
    if (i >= 0) {
      lines[i].qty += qty;
    } else {
      lines.add(CartLine(product: p, qty: qty));
    }
  }

  void setQty(int productId, double qty) {
    final i = lines.indexWhere((l) => l.product.id == productId);
    if (i < 0) return;
    if (qty <= 0) {
      lines.removeAt(i);
    } else {
      lines[i].qty = qty;
    }
  }

  void remove(int productId) => lines.removeWhere((l) => l.product.id == productId);

  bool get isEmpty => lines.isEmpty;

  double get subtotal => lines.fold(0, (s, l) => s + l.total);

  int get qtyTotal => lines.fold<double>(0, (s, l) => s + l.qty).round();

  void clear() => lines.clear();

  List<Map<String, dynamic>> toCheckoutLines() => lines.map((l) => l.toCheckoutJson()).toList();
}

/// Penjualan POS yang menunggu sinkron (offline queue).
/// [clientRef] dibuat SEKALI di perangkat → retry aman (server idempoten).
class PendingSale {
  final String clientRef;
  final String createdAt;
  final List<Map<String, dynamic>> lines;
  final String payMethod;
  final int? customerId;
  String status; // pending | synced | rejected
  String? error;
  String? saleNumber;

  static final _rand = Random.secure();

  /// client_ref unik per penjualan — dibuat SEKALI di perangkat, dipakai semua retry.
  static String newClientRef() {
    final hex = List.generate(16, (_) => _rand.nextInt(256).toRadixString(16).padLeft(2, '0')).join();
    return 'pos-${DateTime.now().millisecondsSinceEpoch}-$hex';
  }

  PendingSale({
    required this.clientRef,
    required this.createdAt,
    required this.lines,
    required this.payMethod,
    this.customerId,
    this.status = 'pending',
    this.error,
    this.saleNumber,
  });

  factory PendingSale.fromJson(Map<String, dynamic> j) => PendingSale(
        clientRef: j['client_ref'] as String,
        createdAt: (j['created_at'] ?? '') as String,
        lines: ((j['lines'] ?? []) as List).map((e) => Map<String, dynamic>.from(e as Map)).toList(),
        payMethod: (j['pay_method'] ?? 'cash') as String,
        customerId: j['customer_id'] == null ? null : asInt(j['customer_id']),
        status: (j['status'] ?? 'pending') as String,
        error: j['error'] as String?,
        saleNumber: j['sale_number'] as String?,
      );

  Map<String, dynamic> toJson() => {
        'client_ref': clientRef,
        'created_at': createdAt,
        'lines': lines,
        'pay_method': payMethod,
        'customer_id': customerId,
        'status': status,
        'error': error,
        'sale_number': saleNumber,
      };

  Map<String, dynamic> toCheckoutPayload() => {
        'lines': lines,
        'pay_method': payMethod,
        if (customerId != null) 'customer_id': customerId,
        'client_ref': clientRef,
      };
}

class Shift {
  final int id;
  final String? openedAt;
  final double openingCash;
  final String status;

  Shift({required this.id, this.openedAt, required this.openingCash, required this.status});

  factory Shift.fromJson(Map<String, dynamic> j) => Shift(
        id: asInt(j['id']),
        openedAt: j['opened_at'] as String?,
        openingCash: asDouble(j['opening_cash']),
        status: (j['status'] ?? 'open') as String,
      );
}

class SaleResult {
  final int id;
  final String number;
  final double subtotal;
  final double tax;
  final double grandTotal;
  final bool replayed;

  SaleResult({
    required this.id,
    required this.number,
    required this.subtotal,
    required this.tax,
    required this.grandTotal,
    required this.replayed,
  });

  factory SaleResult.fromJson(Map<String, dynamic> j) => SaleResult(
        id: asInt(j['id']),
        number: (j['number'] ?? '') as String,
        subtotal: asDouble(j['subtotal']),
        tax: asDouble(j['tax']),
        grandTotal: asDouble(j['grand_total']),
        replayed: j['replayed'] == true,
      );
}
