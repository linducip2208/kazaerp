/// Penyimpanan lokal POS (token, konfigurasi server, cache katalog, antrean penjualan).
library;

import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'models.dart';

class LocalStore {
  LocalStore(this._prefs);

  final SharedPreferences _prefs;

  static const defaultBaseUrl = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'http://10.0.2.2:8099/api/v1',
  );

  static const _kToken = 'pos_token_v1';
  static const _kUserName = 'pos_user_name_v1';
  static const _kBaseUrl = 'pos_base_url_v1';
  static const _kPending = 'pos_pending_sales_v1';
  static const _kProductsCache = 'pos_products_cache_v1';
  static const maxHistory = 300;

  static Future<LocalStore> load() async => LocalStore(await SharedPreferences.getInstance());

  // ---- Sesi ----
  String? get token => _prefs.getString(_kToken);
  String? get userName => _prefs.getString(_kUserName);
  bool get hasSession => (token ?? '').isNotEmpty;

  String get baseUrl => _prefs.getString(_kBaseUrl) ?? defaultBaseUrl;

  Future<void> saveSession({required String token, required String userName, required String baseUrl}) async {
    await _prefs.setString(_kToken, token);
    await _prefs.setString(_kUserName, userName);
    await _prefs.setString(_kBaseUrl, baseUrl);
  }

  Future<void> clearSession() async {
    await _prefs.remove(_kToken);
    await _prefs.remove(_kUserName);
  }

  // ---- Cache katalog (agar offline tetap bisa lihat harga) ----
  Future<void> cacheProducts(List<Product> products) async {
    await _prefs.setString(_kProductsCache, jsonEncode(products.map((p) => p.toJson()).toList()));
  }

  List<Product> cachedProducts() {
    final raw = _prefs.getString(_kProductsCache);
    if (raw == null || raw.isEmpty) return [];
    try {
      return (jsonDecode(raw) as List).map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map))).toList();
    } catch (_) {
      return [];
    }
  }

  // ---- Antrean penjualan ----
  List<PendingSale> loadSales() {
    final raw = _prefs.getStringList(_kPending) ?? [];
    final list = <PendingSale>[];
    for (final s in raw) {
      try {
        list.add(PendingSale.fromJson(Map<String, dynamic>.from(jsonDecode(s) as Map)));
      } catch (_) {
        // entri korup diabaikan
      }
    }
    return list;
  }

  Future<void> saveSales(List<PendingSale> sales) async {
    final trimmed = sales.length > maxHistory ? sales.sublist(sales.length - maxHistory) : sales;
    await _prefs.setStringList(_kPending, trimmed.map((s) => jsonEncode(s.toJson())).toList());
  }
}
