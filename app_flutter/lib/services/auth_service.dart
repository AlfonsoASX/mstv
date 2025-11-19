import 'package:flutter/material.dart';

import '../models/usuario.dart';
import '../utils/constants.dart';

class AuthService extends ChangeNotifier {
  bool _isLoggedIn = false;
  int _intentosFallidos = 0;
  DateTime? _ultimoAcceso;
  Usuario? _usuario;

  bool get isLoggedIn => _isLoggedIn;
  Usuario? get usuario => _usuario;
  int get intentosFallidos => _intentosFallidos;

  Future<bool> login(String usuario, String password) async {
    if (_intentosFallidos >= AppConstants.intentosMaximosLogin) {
      return false;
    }
    await Future<void>.delayed(const Duration(milliseconds: 250));
    final esValido = usuario.isNotEmpty && password.length >= 6;

    if (esValido) {
      _isLoggedIn = true;
      _intentosFallidos = 0;
      _usuario = Usuario.demo();
      _ultimoAcceso = DateTime.now();
      notifyListeners();
      return true;
    } else {
      _intentosFallidos++;
      notifyListeners();
      return false;
    }
  }

  bool get sesionExpirada {
    if (_ultimoAcceso == null) return false;
    return DateTime.now().difference(_ultimoAcceso!) > AppConstants.duracionSesion;
  }

  void refreshSession() {
    _ultimoAcceso = DateTime.now();
  }

  void logout() {
    _isLoggedIn = false;
    _usuario = null;
    notifyListeners();
  }
}
