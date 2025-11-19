import sys
import json
import cv2
import face_recognition

"""
compare_faces.py
--------------------------------------------------
Uso:
    python compare_faces.py <foto_base> <foto_selfie>

Salida:
    {
        "status": "success",
        "match": true/false,
        "score": 0.87
    }
--------------------------------------------------
"""

def load_image(path):
    img = face_recognition.load_image_file(path)
    return face_recognition.face_encodings(img)

def main():
    if len(sys.argv) < 3:
        print(json.dumps({"status": "error", "message": "Faltan argumentos"}))
        return

    foto_base = sys.argv[1]
    foto_selfie = sys.argv[2]

    try:
        base_encodings = load_image(foto_base)
        selfie_encodings = load_image(foto_selfie)

        if not base_encodings:
            print(json.dumps({"status": "error", "message": "No se detectó rostro en foto base"}))
            return
        
        if not selfie_encodings:
            print(json.dumps({"status": "error", "message": "No se detectó rostro en selfie"}))
            return

        distancia = face_recognition.face_distance([base_encodings[0]], selfie_encodings[0])[0]
        score = 1 - distancia  # Convertir a coincidencia (1=perfecto)

        match = score >= 0.60  # Umbral configurable

        print(json.dumps({
            "status": "success",
            "match": bool(match),
            "score": float(round(score, 4))
        }))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    main()
