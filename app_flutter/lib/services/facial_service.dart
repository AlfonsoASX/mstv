class FacialService {
  Future<bool> verificarSelfie() async {
    await Future<void>.delayed(const Duration(milliseconds: 250));
    return true;
  }
}
