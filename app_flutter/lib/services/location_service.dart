import 'dart:math';

class GeoPoint {
  final double lat;
  final double lon;

  const GeoPoint(this.lat, this.lon);
}

class LocationService {
  bool isInsideCircle(GeoPoint user, GeoPoint centro, double radioMetros) {
    final distancia = _calcularDistancia(user.lat, user.lon, centro.lat, centro.lon);
    return distancia <= radioMetros;
  }

  bool isInsidePolygon(List<GeoPoint> poligono, GeoPoint punto) {
    var dentro = false;
    for (int i = 0, j = poligono.length - 1; i < poligono.length; j = i++) {
      final intersecta = ((poligono[i].lon > punto.lon) != (poligono[j].lon > punto.lon)) &&
          (punto.lat <
              (poligono[j].lat - poligono[i].lat) * (punto.lon - poligono[i].lon) /
                      (poligono[j].lon - poligono[i].lon) +
                  poligono[i].lat);
      if (intersecta) dentro = !dentro;
    }
    return dentro;
  }

  double _calcularDistancia(double lat1, double lon1, double lat2, double lon2) {
    const radioTierra = 6371000; // metros
    final dLat = _gradosARadianes(lat2 - lat1);
    final dLon = _gradosARadianes(lon2 - lon1);
    final a =
        sin(dLat / 2) * sin(dLat / 2) + cos(_gradosARadianes(lat1)) * cos(_gradosARadianes(lat2)) * sin(dLon / 2) * sin(dLon / 2);
    final c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return radioTierra * c;
  }

  double _gradosARadianes(double grados) => grados * (pi / 180);
}
