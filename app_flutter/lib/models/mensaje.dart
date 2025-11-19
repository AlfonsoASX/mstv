class Mensaje {
  final String remitente;
  final String contenido;
  final DateTime fecha;
  final bool esPropio;

  const Mensaje({
    required this.remitente,
    required this.contenido,
    required this.fecha,
    this.esPropio = false,
  });

  factory Mensaje.demo({bool propio = false}) {
    return Mensaje(
      remitente: propio ? 'Tú' : 'Administración',
      contenido: propio ? 'Entendido, en camino.' : 'Favor de revisar el perímetro este turno.',
      fecha: DateTime.now().subtract(const Duration(minutes: 5)),
      esPropio: propio,
    );
  }
}
