import 'package:flutter/material.dart';

import '../models/checada.dart';
import '../services/api_service.dart';
import '../widgets/tarjeta_bitacora.dart';

class BitacoraPage extends StatefulWidget {
  const BitacoraPage({super.key});

  @override
  State<BitacoraPage> createState() => _BitacoraPageState();
}

class _BitacoraPageState extends State<BitacoraPage> {
  final _api = ApiService();
  late Future<List<Checada>> _future;

  @override
  void initState() {
    super.initState();
    _future = _api.obtenerBitacora();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bitácora de checadas')),
      body: FutureBuilder<List<Checada>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          final data = snapshot.data ?? [];
          if (data.isEmpty) {
            return const Center(child: Text('Sin checadas disponibles'));
          }
          return ListView.builder(
            padding: const EdgeInsets.all(8),
            itemCount: data.length,
            itemBuilder: (_, i) => TarjetaBitacora(checada: data[i]),
          );
        },
      ),
    );
  }
}
