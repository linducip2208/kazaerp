import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pos_kaza/local_store.dart';
import 'package:pos_kaza/screens/login_screen.dart';
import 'package:pos_kaza/screens/pos_screen.dart' show formatIdr;
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('formatIdr memberi pemisah ribuan', () {
    expect(formatIdr(0), 'Rp 0');
    expect(formatIdr(1500), 'Rp 1.500');
    expect(formatIdr(1234567), 'Rp 1.234.567');
  });

  testWidgets('login menampilkan validasi saat field kosong', (tester) async {
    SharedPreferences.setMockInitialValues({});
    final store = await LocalStore.load();
    await tester.pumpWidget(MaterialApp(home: LoginScreen(store: store)));
    expect(find.text('Masuk'), findsOneWidget);
    await tester.tap(find.text('Masuk'));
    await tester.pump();
    expect(find.textContaining('wajib diisi'), findsOneWidget);
  });

  testWidgets('login memuat alamat server tersimpan', (tester) async {
    SharedPreferences.setMockInitialValues({'pos_base_url_v1': 'http://server.local:8099/api/v1'});
    final store = await LocalStore.load();
    await tester.pumpWidget(MaterialApp(home: LoginScreen(store: store)));
    expect(find.text('http://server.local:8099/api/v1'), findsOneWidget);
  });
}
