import 'package:flutter/material.dart';

import '../models/video.dart';
import '../services/api_service.dart';
import '../widgets/video_card.dart';

class CapacitacionPage extends StatefulWidget {
  const CapacitacionPage({super.key});

  @override
  State<CapacitacionPage> createState() => _CapacitacionPageState();
}

class _CapacitacionPageState extends State<CapacitacionPage> {
  final _api = ApiService();
  late Future<List<Video>> _future;

  @override
  void initState() {
    super.initState();
    _future = _api.obtenerCapacitacion();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Capacitación')),
      body: FutureBuilder<List<Video>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          final data = snapshot.data ?? [];
          if (data.isEmpty) {
            return const Center(child: Text('No hay videos asignados'));
          }
          return ListView.builder(
            padding: const EdgeInsets.all(8),
            itemCount: data.length,
            itemBuilder: (_, i) => VideoCard(video: data[i]),
          );
        },
      ),
    );
  }
}
