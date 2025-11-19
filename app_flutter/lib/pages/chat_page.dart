import 'package:flutter/material.dart';

import '../models/mensaje.dart';
import '../services/api_service.dart';
import '../utils/antigroserias.dart';

class ChatPage extends StatefulWidget {
  const ChatPage({super.key});

  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> {
  final _api = ApiService();
  final _controller = TextEditingController();
  late Future<List<Mensaje>> _future;
  final _mensajesLocales = <Mensaje>[];

  @override
  void initState() {
    super.initState();
    _future = _api.obtenerChat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _enviar() {
    if (_controller.text.isEmpty) return;
    final limpio = limpiaGroserias(_controller.text);
    setState(() {
      _mensajesLocales.add(
        Mensaje(remitente: 'Tú', contenido: limpio, fecha: DateTime.now(), esPropio: true),
      );
    });
    _controller.clear();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Chat con Administración')),
      body: Column(
        children: [
          Expanded(
            child: FutureBuilder<List<Mensaje>>(
              future: _future,
              builder: (context, snapshot) {
                final mensajes = [...(snapshot.data ?? []), ..._mensajesLocales];
                if (mensajes.isEmpty) {
                  return const Center(child: Text('Sin mensajes'));
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(8),
                  itemCount: mensajes.length,
                  itemBuilder: (_, i) {
                    final msg = mensajes[i];
                    return Align(
                      alignment: msg.esPropio ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.symmetric(vertical: 4),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: msg.esPropio ? Colors.blueAccent : Colors.grey[300],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          msg.contenido,
                          style: TextStyle(color: msg.esPropio ? Colors.white : Colors.black),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: const InputDecoration(hintText: 'Escribe un mensaje'),
                  ),
                ),
                IconButton(
                  onPressed: _enviar,
                  icon: const Icon(Icons.send),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
