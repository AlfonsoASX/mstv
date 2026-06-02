(function (window) {
    'use strict';

    const MODEL_ID = 'face-api.js-faceRecognitionNet';
    const DESCRIPTOR_SIZE = 128;

    let modelUri = 'assets/face-api/models';
    let minDetectionScore = 0.55;
    let inputSize = 320;
    let loadPromise = null;

    function configure(options) {
        options = options || {};

        if (options.modelUri && options.modelUri !== modelUri) {
            modelUri = options.modelUri;
            loadPromise = null;
        }

        if (typeof options.minDetectionScore === 'number') {
            minDetectionScore = options.minDetectionScore;
        }

        if (typeof options.inputSize === 'number') {
            inputSize = options.inputSize;
        }
    }

    function ensureFaceApi() {
        if (!window.faceapi) {
            throw new Error('La libreria de reconocimiento facial no esta disponible.');
        }
    }

    async function loadModels() {
        ensureFaceApi();

        if (!loadPromise) {
            loadPromise = Promise.all([
                window.faceapi.nets.tinyFaceDetector.loadFromUri(modelUri),
                window.faceapi.nets.faceLandmark68Net.loadFromUri(modelUri),
                window.faceapi.nets.faceRecognitionNet.loadFromUri(modelUri)
            ]);
        }

        await loadPromise;
    }

    function detectorOptions(options) {
        return new window.faceapi.TinyFaceDetectorOptions({
            inputSize: (options && options.inputSize) || inputSize,
            scoreThreshold: (options && options.minDetectionScore) || minDetectionScore
        });
    }

    function normalizeDescriptor(rawDescriptor) {
        const descriptor = Array.from(rawDescriptor || []).map(function (value) {
            return Number(Number(value).toFixed(8));
        });

        if (descriptor.length !== DESCRIPTOR_SIZE || descriptor.some(function (value) { return !Number.isFinite(value); })) {
            throw new Error('El descriptor facial generado no tiene un formato valido.');
        }

        return descriptor;
    }

    async function analyze(input, options) {
        await loadModels();

        const detections = await window.faceapi
            .detectAllFaces(input, detectorOptions(options))
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (!detections.length) {
            return {
                ok: false,
                reason: 'NO_FACE',
                message: 'No se detecto un rostro. Toma la foto de frente y con buena luz.',
                count: 0
            };
        }

        if (detections.length > 1) {
            return {
                ok: false,
                reason: 'MULTIPLE_FACES',
                message: 'Se detecto mas de un rostro. Solo debe aparecer el colaborador.',
                count: detections.length
            };
        }

        const detection = detections[0];

        return {
            ok: true,
            reason: 'MATCHABLE_FACE',
            message: 'Rostro detectado.',
            count: 1,
            score: Number((detection.detection.score || 0).toFixed(4)),
            descriptor: normalizeDescriptor(detection.descriptor),
            model: MODEL_ID
        };
    }

    function imageFromFile(file) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            const url = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };

            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('No fue posible leer la imagen seleccionada.'));
            };

            image.src = url;
        });
    }

    async function analyzeFile(file, options) {
        const image = await imageFromFile(file);
        return analyze(image, options);
    }

    function descriptorJson(descriptor) {
        return JSON.stringify(normalizeDescriptor(descriptor));
    }

    window.MSTVFacial = {
        MODEL_ID: MODEL_ID,
        configure: configure,
        loadModels: loadModels,
        analyze: analyze,
        analyzeFile: analyzeFile,
        descriptorJson: descriptorJson
    };
})(window);
