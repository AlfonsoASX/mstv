class Video {
  final String titulo;
  final String descripcion;
  final String url;

  const Video({
    required this.titulo,
    required this.descripcion,
    required this.url,
  });

  factory Video.demo(int index) {
    return Video(
      titulo: 'Video de inducción #$index',
      descripcion: 'Buenas prácticas y protocolos de seguridad ($index)',
      url: 'https://www.example.com/video/$index',
    );
  }
}
