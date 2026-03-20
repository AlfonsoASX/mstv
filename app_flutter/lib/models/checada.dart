class Checada {
  final String tipo;
  final DateTime fecha;
  final String estado;
  final String comentario;

  const Checada({
    required this.tipo,
    required this.fecha,
    required this.estado,
    this.comentario = '',
  });

  factory Checada.demo(String tipo, String estado) {
    return Checada(
      tipo: tipo,
      fecha: DateTime.now(),
      estado: estado,
      comentario: estado == 'Rechazada' ? 'Fuera de geocerca' : 'OK',
    );
  }
}
