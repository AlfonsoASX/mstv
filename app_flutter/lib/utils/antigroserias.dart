final _palabrasBloqueadas = <String>{
  'groseria',
  'insulto',
};

String limpiaGroserias(String texto) {
  var limpio = texto;
  for (final palabra in _palabrasBloqueadas) {
    final regex = RegExp(palabra, caseSensitive: false);
    limpio = limpio.replaceAll(regex, '***');
  }
  return limpio;
}
