/// Klien HTTP untuk API kazaerp /api/v1 (contr: login, produk, pelanggan, shift, checkout).
library;

import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import 'models.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.isNetwork = false});

  final String message;
  final int? statusCode;
  final bool isNetwork;

  /// 4xx = permintaan ditolak server (mis. validasi) → jangan dianggap offline.
  bool get isClientError => statusCode != null && statusCode! >= 400 && statusCode! < 500;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required this.baseUrl, this.token, http.Client? httpClient}) : _http = httpClient ?? http.Client();

  final String baseUrl;
  String? token;
  final http.Client _http;

  static const _timeout = Duration(seconds: 15);

  Uri _uri(String path, [Map<String, Object?>? query]) {
    final u = Uri.parse('$baseUrl$path');
    if (query == null || query.isEmpty) return u;
    return u.replace(queryParameters: query.map((k, v) => MapEntry(k, v.toString())));
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if ((token ?? '').isNotEmpty) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() run) async {
    late http.Response res;
    try {
      res = await run().timeout(_timeout);
    } on TimeoutException {
      throw ApiException('Server tidak merespons (timeout).', isNetwork: true);
    } catch (e) {
      throw ApiException('Tidak dapat terhubung ke server.', isNetwork: true);
    }
    Map<String, dynamic> body = const {};
    if (res.body.isNotEmpty) {
      try {
        body = Map<String, dynamic>.from(jsonDecode(res.body) as Map);
      } on FormatException {
        throw ApiException('Respons server tidak valid.', statusCode: res.statusCode);
      }
    }
    if (res.statusCode >= 200 && res.statusCode < 300) return body;
    throw ApiException(body['message']?.toString() ?? 'HTTP ${res.statusCode}', statusCode: res.statusCode);
  }

  /// Login → token (tanpa instance; dipakai sebelum sesi tersimpan).
  static Future<String> login({
    required String baseUrl,
    required String email,
    required String password,
    http.Client? httpClient,
  }) async {
    final c = ApiClient(baseUrl: baseUrl, httpClient: httpClient);
    final body = await c._send(() => c._http.post(
          c._uri('/login'),
          headers: c._headers,
          body: jsonEncode({'email': email, 'password': password}),
        ));
    final token = body['token'] as String?;
    if (token == null || token.isEmpty) {
      throw ApiException('Token tidak diterima dari server.');
    }
    return token;
  }

  Future<List<Product>> products({String? q, int? customerId}) async {
    final body = await _send(() => _http.get(
          _uri('/products', {'q': (q ?? '').trim(), 'customer_id': ?customerId}),
          headers: _headers,
        ));
    return ((body['data'] ?? []) as List).map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<List<Customer>> customers({String? q}) async {
    final body = await _send(() => _http.get(
          _uri('/customers', {'q': (q ?? '').trim()}),
          headers: _headers,
        ));
    return ((body['data'] ?? []) as List).map((e) => Customer.fromJson(Map<String, dynamic>.from(e as Map))).toList();
  }

  Future<Shift?> currentShift() async {
    final body = await _send(() => _http.get(_uri('/pos/shift'), headers: _headers));
    final data = body['data'];
    if (data == null) return null;
    return Shift.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<Shift> openShift(double openingCash) async {
    final body = await _send(() => _http.post(
          _uri('/pos/shift/open'),
          headers: _headers,
          body: jsonEncode({'opening_cash': openingCash}),
        ));
    return Shift.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<Map<String, dynamic>> closeShift(double closingCash) async {
    final body = await _send(() => _http.post(
          _uri('/pos/shift/close'),
          headers: _headers,
          body: jsonEncode({'closing_cash': closingCash}),
        ));
    return Map<String, dynamic>.from((body['data'] ?? {}) as Map);
  }

  /// Checkout idempoten via `client_ref`; replay (200) tetap dianggap sukses.
  Future<SaleResult> checkout(PendingSale sale) async {
    final body = await _send(() => _http.post(
          _uri('/pos/checkout'),
          headers: _headers,
          body: jsonEncode(sale.toCheckoutPayload()),
        ));
    return SaleResult.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }
}
