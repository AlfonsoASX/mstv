import 'dart:async';

import '../models/checada.dart';
import '../models/incidencia.dart';
import '../models/mensaje.dart';
import '../models/turno.dart';
import '../models/usuario.dart';
import '../models/video.dart';

class ApiService {
  Future<Usuario> obtenerPerfil() async {
    return Future<Usuario>.delayed(
      const Duration(milliseconds: 300),
      Usuario.demo,
    );
  }

  Future<Turno> obtenerProximoTurno() async {
    return Future<Turno>.delayed(
      const Duration(milliseconds: 300),
      () => Turno.demo(),
    );
  }

  Future<List<Checada>> obtenerBitacora() async {
    return Future<List<Checada>>.delayed(
      const Duration(milliseconds: 300),
      () => [
        Checada.demo('Llegada', 'Aprobada'),
        Checada.demo('Salida', 'Aprobada'),
        Checada.demo('Extra', 'Rechazada'),
      ],
    );
  }

  Future<List<Incidencia>> obtenerIncidencias() async {
    return Future<List<Incidencia>>.delayed(
      const Duration(milliseconds: 300),
      () => [
        Incidencia.demo(),
        Incidencia.demo(estado: 'Atendido'),
      ],
    );
  }

  Future<List<Mensaje>> obtenerChat() async {
    return Future<List<Mensaje>>.delayed(
      const Duration(milliseconds: 300),
      () => [Mensaje.demo(), Mensaje.demo(propio: true)],
    );
  }

  Future<List<Video>> obtenerCapacitacion() async {
    return Future<List<Video>>.delayed(
      const Duration(milliseconds: 300),
      () => List<Video>.generate(3, (index) => Video.demo(index + 1)),
    );
  }
}
