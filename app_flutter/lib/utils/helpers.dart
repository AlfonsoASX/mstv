import 'package:intl/intl.dart';

String formateaFechaHora(DateTime fecha) {
  final formato = DateFormat('dd/MM/yyyy HH:mm');
  return formato.format(fecha);
}

String formateaHoraCorta(DateTime fecha) {
  final formato = DateFormat('HH:mm');
  return formato.format(fecha);
}
