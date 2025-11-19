class Usuario {
  final String nombre;
  final String rol;
  final String sitioAsignado;
  final String fotoUrl;

  const Usuario({
    required this.nombre,
    required this.rol,
    required this.sitioAsignado,
    required this.fotoUrl,
  });

  factory Usuario.demo() {
    return const Usuario(
      nombre: 'Carlos Méndez',
      rol: 'Guardia',
      sitioAsignado: 'Torre Norte',
      fotoUrl: 'https://via.placeholder.com/150',
    );
  }
}
