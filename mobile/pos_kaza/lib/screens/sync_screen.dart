import 'package:flutter/material.dart';

import '../api_client.dart';
import '../local_store.dart';
import '../models.dart';
import '../pos_repository.dart';

class SyncScreen extends StatefulWidget {
  const SyncScreen({super.key, required this.store});

  final LocalStore store;

  @override
  State<SyncScreen> createState() => _SyncScreenState();
}

class _SyncScreenState extends State<SyncScreen> {
  late final PosRepository _repo = PosRepository(
    api: ApiClient(baseUrl: widget.store.baseUrl, token: widget.store.token),
    store: widget.store,
  );

  List<PendingSale> _sales = [];
  bool _busy = false;
  String? _report;

  @override
  void initState() {
    super.initState();
    _load();
  }

  void _load() {
    setState(() => _sales = widget.store.loadSales().reversed.toList());
  }

  Future<void> _sync() async {
    setState(() => _busy = true);
    final report = await _repo.syncPending();
    if (!mounted) return;
    setState(() {
      _busy = false;
      _report = report.summary;
    });
    _load();
  }

  Future<void> _retryRejected() async {
    await _repo.retryRejected();
    _load();
    if (mounted) setState(() => _report = 'Transaksi ditolak dikembalikan ke antrean — tekan Sinkron sekarang.');
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'synced':
        return Colors.green;
      case 'rejected':
        return Theme.of(context).colorScheme.error;
      default:
        return Colors.orange;
    }
  }

  IconData _statusIcon(String status) {
    switch (status) {
      case 'synced':
        return Icons.cloud_done;
      case 'rejected':
        return Icons.error_outline;
      default:
        return Icons.cloud_queue;
    }
  }

  @override
  Widget build(BuildContext context) {
    final pending = _sales.where((s) => s.status == 'pending').length;
    final rejected = _sales.where((s) => s.status == 'rejected').length;
    final synced = _sales.where((s) => s.status == 'synced').length;

    return Scaffold(
      appBar: AppBar(title: const Text('Antrean Sinkron')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: Text('$pending menunggu • $rejected ditolak • $synced tersinkron',
                      style: const TextStyle(fontWeight: FontWeight.bold)),
                ),
                if (_busy) const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Row(
              children: [
                FilledButton.icon(
                  onPressed: _busy ? null : _sync,
                  icon: const Icon(Icons.sync),
                  label: const Text('Sinkron sekarang'),
                ),
                const SizedBox(width: 8),
                if (rejected > 0)
                  OutlinedButton(onPressed: _busy ? null : _retryRejected, child: const Text('Kirim ulang yang ditolak')),
              ],
            ),
          ),
          if (_report != null)
            Padding(
              padding: const EdgeInsets.all(12),
              child: Text(_report!, style: TextStyle(color: Theme.of(context).colorScheme.primary)),
            ),
          const Divider(height: 1),
          Expanded(
            child: _sales.isEmpty
                ? const Center(child: Text('Belum ada transaksi tersimpan.'))
                : ListView.builder(
                    itemCount: _sales.length,
                    itemBuilder: (ctx, i) {
                      final s = _sales[i];
                      final qty = s.lines.fold<double>(0, (a, l) => a + (double.tryParse('${l['qty']}') ?? 0));
                      return ListTile(
                        leading: Icon(_statusIcon(s.status), color: _statusColor(s.status)),
                        title: Text(s.saleNumber ?? s.clientRef, maxLines: 1, overflow: TextOverflow.ellipsis),
                        subtitle: Text('${qty.toStringAsFixed(0)} item • ${s.payMethod} • ${s.createdAt}'
                            '${s.error != null ? '\n${s.error}' : ''}'),
                        isThreeLine: s.error != null,
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
