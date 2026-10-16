import 'package:flutter/material.dart';

import '../api_client.dart';
import '../local_store.dart';
import '../models.dart';
import '../pos_repository.dart';
import 'login_screen.dart';
import 'sync_screen.dart';

double _dbl(Object? v) => v == null ? 0 : double.parse(v.toString());

String formatIdr(double v) {
  final s = v.toStringAsFixed(0);
  final buf = StringBuffer();
  for (var i = 0; i < s.length; i++) {
    final rem = s.length - i;
    buf.write(s[i]);
    if (rem > 1 && rem % 3 == 1) buf.write('.');
  }
  return 'Rp $buf';
}

class PosScreen extends StatefulWidget {
  const PosScreen({super.key, required this.store});

  final LocalStore store;

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  late final ApiClient _api = ApiClient(baseUrl: widget.store.baseUrl, token: widget.store.token);
  late final PosRepository _repo = PosRepository(api: _api, store: widget.store);

  final _cart = Cart();
  final _search = TextEditingController();

  List<Product> _products = [];
  List<Customer> _customers = [];
  Shift? _shift;
  bool _loading = true;
  bool _offline = false;
  bool _sessionExpired = false;
  String? _notice;
  int _pending = 0;

  @override
  void initState() {
    super.initState();
    _refreshAll();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _refreshAll() async {
    setState(() => _loading = true);
    try {
      final shift = await _api.currentShift();
      final products = await _api.products(q: _search.text);
      await widget.store.cacheProducts(products);
      if (!mounted) return;
      setState(() {
        _shift = shift;
        _products = products;
        _offline = false;
        _notice = null;
        _loading = false;
      });
      _autoSync();
    } on ApiException catch (e) {
      final cached = widget.store.cachedProducts();
      if (!mounted) return;
      setState(() {
        _offline = true;
        _loading = false;
        _sessionExpired = e.statusCode == 401;
        if (cached.isNotEmpty) _products = cached;
        _notice = e.statusCode == 401 ? 'Sesi berakhir — login ulang.' : e.message;
      });
    }
    await _refreshPendingCount();
  }

  Future<void> _autoSync() async {
    final report = await _repo.syncPending();
    if (!mounted) return;
    await _refreshPendingCount();
    if (!mounted || report.synced == 0) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Antrean lama tersinkron: ${report.synced} transaksi.')));
  }

  Future<void> _refreshPendingCount() async {
    final n = await _repo.pendingCount();
    if (mounted) setState(() => _pending = n);
  }

  Future<void> _searchProducts() async {
    if (_offline) {
      setState(() {}); // filter lokal
      return;
    }
    try {
      final products = await _api.products(q: _search.text);
      await widget.store.cacheProducts(products);
      if (!mounted) return;
      setState(() => _products = products);
    } on ApiException catch (e) {
      if (mounted) setState(() => _notice = e.message);
    }
  }

  List<Product> get _visibleProducts {
    final q = _search.text.trim().toLowerCase();
    if (!_offline || q.isEmpty) return _products;
    return _products
        .where((p) => p.name.toLowerCase().contains(q) || p.sku.toLowerCase().contains(q))
        .toList();
  }

  Future<void> _openShiftDialog() async {
    final ctrl = TextEditingController(text: '0');
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Buka Shift'),
        content: TextField(
          controller: ctrl,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(labelText: 'Kas awal (Rp)'),
          autofocus: true,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Buka')),
        ],
      ),
    );
    final amount = double.tryParse(ctrl.text.trim()) ?? 0;
    ctrl.dispose();
    if (ok != true || !mounted) return;
    try {
      final shift = await _api.openShift(amount);
      if (!mounted) return;
      setState(() {
        _shift = shift;
        _notice = null;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _notice = e.message);
    }
  }

  Future<void> _closeShiftDialog() async {
    final ctrl = TextEditingController(text: '0');
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Tutup Shift'),
        content: TextField(
          controller: ctrl,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(labelText: 'Kas fisik akhir (Rp)'),
          autofocus: true,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Tutup')),
        ],
      ),
    );
    final amount = double.tryParse(ctrl.text.trim()) ?? 0;
    ctrl.dispose();
    if (ok != true || !mounted) return;
    try {
      final summary = await _api.closeShift(amount);
      if (!mounted) return;
      setState(() => _shift = null);
      await showDialog<void>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Shift ditutup'),
          content: Text('Penjualan sistem: ${formatIdr(_dbl(summary['system_total']))}\n'
              'Kas seharusnya: ${formatIdr(_dbl(summary['expected_cash']))}\n'
              'Selisih: ${formatIdr(_dbl(summary['difference']))}'),
          actions: [FilledButton(onPressed: () => Navigator.pop(ctx), child: const Text('OK'))],
        ),
      );
    } on ApiException catch (e) {
      if (mounted) setState(() => _notice = e.message);
    }
  }

  Future<void> _loadCustomers() async {
    if (_customers.isNotEmpty || _offline) return;
    try {
      final list = await _api.customers();
      if (mounted) setState(() => _customers = list);
    } on ApiException {
      // pelanggan opsional; abaikan bila gagal
    }
  }

  Future<void> _openCart() async {
    if (_cart.isEmpty) return;
    await _loadCustomers();
    if (!mounted) return;
    final intent = await showModalBottomSheet<_CheckoutIntent>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => _CartSheet(cart: _cart, customers: _customers),
    );
    if (intent == null || !mounted) return;
    await _doCheckout(intent);
  }

  Future<void> _doCheckout(_CheckoutIntent intent) async {
    final sale = _repo.buildSale(lines: _cart.toCheckoutLines(), payMethod: intent.payMethod, customerId: intent.customerId);
    final status = await _repo.submitNew(sale);
    if (!mounted) return;
    setState(() => _cart.clear());
    await _refreshPendingCount();
    if (!mounted) return;
    final msg = switch (status) {
      'synced' => 'Tersimpan: ${sale.saleNumber ?? sale.clientRef}',
      'rejected' => 'Ditolak server: ${sale.error ?? 'tidak diketahui'} — perbaiki dari halaman Antrean.',
      _ => 'Offline — transaksi aman di antrean (${sale.clientRef}).',
    };
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _logout() async {
    await widget.store.clearSession();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => LoginScreen(store: widget.store)),
      (r) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('POS — ${widget.store.userName ?? ''}'),
        actions: [
          IconButton(onPressed: _refreshAll, icon: const Icon(Icons.refresh), tooltip: 'Muat ulang'),
          IconButton(
            onPressed: () async {
              await Navigator.of(context).push(MaterialPageRoute(builder: (_) => SyncScreen(store: widget.store)));
              await _refreshPendingCount();
            },
            tooltip: 'Antrean sinkron',
            icon: Badge(
              isLabelVisible: _pending > 0,
              label: Text('$_pending'),
              child: const Icon(Icons.cloud_upload_outlined),
            ),
          ),
          PopupMenuButton<String>(
            onSelected: (v) {
              if (v == 'close') _closeShiftDialog();
              if (v == 'logout') _logout();
            },
            itemBuilder: (ctx) => [
              if (_shift != null) const PopupMenuItem(value: 'close', child: Text('Tutup shift')),
              const PopupMenuItem(value: 'logout', child: Text('Logout')),
            ],
          ),
        ],
      ),
      body: _buildBody(),
      bottomNavigationBar: _cart.isEmpty
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
                child: FilledButton.icon(
                  onPressed: _openCart,
                  icon: const Icon(Icons.point_of_sale),
                  label: Text('${_cart.qtyTotal} item • ${formatIdr(_cart.subtotal)} — Bayar'),
                ),
              ),
            ),
    );
  }

  Widget _buildBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    return Column(
      children: [
        if (_offline || _notice != null)
          Container(
            width: double.infinity,
            color: Theme.of(context).colorScheme.errorContainer,
            padding: const EdgeInsets.all(8),
            child: Row(
              children: [
                Expanded(child: Text(_notice ?? 'Mode offline — katalog dari cache tersimpan.')),
                if (_sessionExpired)
                  TextButton(onPressed: _logout, child: const Text('Login ulang'))
                else if (_offline)
                  TextButton(onPressed: _refreshAll, child: const Text('Coba lagi')),
              ],
            ),
          ),
        if (_shift == null)
          Expanded(
            child: Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.lock_clock, size: 48),
                  const SizedBox(height: 8),
                  const Text('Belum ada shift terbuka untuk kasir ini.'),
                  const SizedBox(height: 12),
                  FilledButton(onPressed: _openShiftDialog, child: const Text('Buka Shift')),
                ],
              ),
            ),
          )
        else ...[
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 0),
            child: TextField(
              controller: _search,
              decoration: InputDecoration(
                hintText: 'Cari SKU / nama produk',
                prefixIcon: const Icon(Icons.search),
                border: const OutlineInputBorder(),
                suffixIcon: IconButton(icon: const Icon(Icons.close), onPressed: () { _search.clear(); _searchProducts(); }),
              ),
              onSubmitted: (_) => _searchProducts(),
            ),
          ),
          Expanded(
            child: _visibleProducts.isEmpty
                ? const Center(child: Text('Tidak ada produk.'))
                : ListView.builder(
                    itemCount: _visibleProducts.length,
                    itemBuilder: (ctx, i) {
                      final p = _visibleProducts[i];
                      return ListTile(
                        title: Text(p.name),
                        subtitle: Text('${p.sku} • ${p.tier}'),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(formatIdr(p.price)),
                            IconButton(onPressed: () => setState(() => _cart.add(p)), icon: const Icon(Icons.add_circle_outline)),
                          ],
                        ),
                        onTap: () => setState(() => _cart.add(p)),
                      );
                    },
                  ),
          ),
        ],
      ],
    );
  }
}

class _CheckoutIntent {
  _CheckoutIntent(this.payMethod, this.customerId);

  final String payMethod;
  final int? customerId;
}

class _CartSheet extends StatefulWidget {
  const _CartSheet({required this.cart, required this.customers});

  final Cart cart;
  final List<Customer> customers;

  @override
  State<_CartSheet> createState() => _CartSheetState();
}

class _CartSheetState extends State<_CartSheet> {
  String _payMethod = 'cash';
  int? _customerId;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Padding(padding: EdgeInsets.all(12), child: Text('Keranjang', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold))),
            Flexible(
              child: ListView(
                shrinkWrap: true,
                children: [
                  for (final line in widget.cart.lines)
                    ListTile(
                      dense: true,
                      title: Text(line.product.name),
                      subtitle: Text(formatIdr(line.product.price)),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          IconButton(icon: const Icon(Icons.remove), onPressed: () => setState(() => widget.cart.setQty(line.product.id, line.qty - 1))),
                          Text(line.qty.toStringAsFixed(0)),
                          IconButton(icon: const Icon(Icons.add), onPressed: () => setState(() => widget.cart.setQty(line.product.id, line.qty + 1))),
                        ],
                      ),
                    ),
                ],
              ),
            ),
            if (widget.customers.isNotEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                child: DropdownButtonFormField<int?>(
                  initialValue: _customerId,
                  decoration: const InputDecoration(labelText: 'Pelanggan (opsional — harga tier)'),
                  items: [
                    const DropdownMenuItem<int?>(value: null, child: Text('Tanpa pelanggan (retail)')),
                    for (final c in widget.customers) DropdownMenuItem<int?>(value: c.id, child: Text('${c.name} (${c.tier})')),
                  ],
                  onChanged: (v) => setState(() => _customerId = v),
                ),
              ),
            const SizedBox(height: 8),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'cash', label: Text('Tunai')),
                ButtonSegment(value: 'transfer', label: Text('Transfer')),
                ButtonSegment(value: 'qris', label: Text('QRIS')),
              ],
              selected: {_payMethod},
              onSelectionChanged: (s) => setState(() => _payMethod = s.first),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Expanded(child: Text('Total: ${formatIdr(widget.cart.subtotal)}', style: const TextStyle(fontWeight: FontWeight.bold))),
                  FilledButton(
                    onPressed: () => Navigator.pop(context, _CheckoutIntent(_payMethod, _customerId)),
                    child: const Text('Bayar & Simpan'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
