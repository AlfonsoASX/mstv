class Turno {
  final String sitio;
  final DateTime inicio;
  final DateTime fin;
  final bool esExtra;

  const Turno({
    required this.sitio,
    required this.inicio,
    required this.fin,
    this.esExtra = false,
  });

  factory Turno.demo({bool extra = false}) {
    final now = DateTime.now();
    return Turno(
      sitio: extra ? 'Sucursal Centro (extra)' : 'Torre Norte',
      inicio: DateTime(now.year, now.month, now.day, now.hour + 1),
      fin: DateTime(now.year, now.month, now.day, now.hour + 9),
      esExtra: extra,
    );
  }
}
