import 'package:flutter/material.dart';

class AppTheme {
  static ThemeData light() {
    return ThemeData(
      primarySwatch: Colors.blueGrey,
      scaffoldBackgroundColor: Colors.grey[100],
      useMaterial3: false,
    );
  }
}
