import 'package:flutter/material.dart';

import '../models/usuario.dart';
import '../services/api_service.dart';

class UsuarioProvider extends ChangeNotifier {
  final _api = ApiService();
  Usuario? _usuario;
  bool _cargando = false;

  Usuario? get usuario => _usuario;
  bool get cargando => _cargando;

  Future<void> cargarPerfil() async {
    _cargando = true;
    notifyListeners();
    _usuario = await _api.obtenerPerfil();
    _cargando = false;
    notifyListeners();
  }
}
