import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/usuario_provider.dart';

class PerfilPage extends StatefulWidget {
  const PerfilPage({super.key});

  @override
  State<PerfilPage> createState() => _PerfilPageState();
}

class _PerfilPageState extends State<PerfilPage> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => context.read<UsuarioProvider>().cargarPerfil());
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<UsuarioProvider>();
    final usuario = provider.usuario;

    return Scaffold(
      appBar: AppBar(title: const Text('Perfil')),
      body: provider.cargando
          ? const Center(child: CircularProgressIndicator())
          : usuario == null
              ? const Center(child: Text('No se pudo cargar la información'))
              : Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 40,
                        backgroundImage: NetworkImage(usuario.fotoUrl),
                      ),
                      const SizedBox(height: 12),
                      Text(usuario.nombre, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      Text(usuario.rol),
                      const SizedBox(height: 8),
                      Text('Sitio: ${usuario.sitioAsignado}'),
                    ],
                  ),
                ),
    );
  }
}
