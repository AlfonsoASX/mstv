class Incidencia {
  final String tipo;
  final String prioridad;
  final String descripcion;
  final String estado;
  final DateTime fecha;

  const Incidencia({
    required this.tipo,
    required this.prioridad,
    required this.descripcion,
    required this.estado,
    required this.fecha,
  });

  factory Incidencia.demo({String estado = 'Pendiente'}) {
    return Incidencia(
      tipo: 'Seguridad',
      prioridad: 'Alta',
      descripcion: 'Puerta principal forzada, se reporta al supervisor.',
      estado: estado,
      fecha: DateTime.now().subtract(const Duration(hours: 2)),
    );
  }
}
