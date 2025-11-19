import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

// Services
import 'services/auth_service.dart';

// Pages
import 'pages/login_page.dart';
import 'pages/dashboard_guardia.dart';
import 'pages/bitacora_page.dart';
import 'pages/incidencia_page.dart';
import 'pages/chat_page.dart';
import 'pages/capacitacion_page.dart';
import 'pages/checada_page.dart';
import 'pages/perfil_page.dart';

void main() {
  runApp(const SeguridadApp());
}

class SeguridadApp extends StatelessWidget {
  const SeguridadApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthService>(create: (_) => AuthService()),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'Seguridad Privada',
        theme: ThemeData(
          primarySwatch: Colors.blueGrey,
          fontFamily: 'Roboto',
          scaffoldBackgroundColor: Colors.grey[100],
          elevatedButtonTheme: ElevatedButtonThemeData(
            style: ElevatedButton.styleFrom(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
            ),
          ),
        ),
        home: const AuthWrapper(),
        routes: {
          '/login': (_) => const LoginPage(),
          '/dashboard': (_) => const DashboardGuardia(),
          '/checada': (_) => const ChecadaPage(),
          '/incidencia': (_) => const IncidenciaPage(),
          '/bitacora': (_) => const BitacoraPage(),
          '/capacitacion': (_) => const CapacitacionPage(),
          '/chat': (_) => const ChatPage(),
          '/perfil': (_) => const PerfilPage(),
        },
      ),
    );
  }
}

/// Redirección automática según estado de sesión
class AuthWrapper extends StatelessWidget {
  const AuthWrapper({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Provider.of<AuthService>(context);

    if (auth.isLoggedIn) {
      return const DashboardGuardia();
    } else {
      return const LoginPage();
    }
  }
}
