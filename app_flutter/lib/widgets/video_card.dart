import 'package:flutter/material.dart';

import '../models/video.dart';

class VideoCard extends StatelessWidget {
  final Video video;

  const VideoCard({super.key, required this.video});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: const Icon(Icons.play_circle_fill, color: Colors.redAccent),
        title: Text(video.titulo),
        subtitle: Text(video.descripcion),
        onTap: () => ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Abrir ${video.url}')),
        ),
      ),
    );
  }
}
