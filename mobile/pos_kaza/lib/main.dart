import 'package:flutter/material.dart';

import 'local_store.dart';
import 'screens/login_screen.dart';
import 'screens/pos_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final store = await LocalStore.load();
  runApp(PosKazaApp(store: store));
}

class PosKazaApp extends StatelessWidget {
  const PosKazaApp({super.key, required this.store});

  final LocalStore store;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'POS Kaza',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(colorSchemeSeed: const Color(0xFF0F766E), useMaterial3: true),
      home: store.hasSession ? PosScreen(store: store) : LoginScreen(store: store),
    );
  }
}
