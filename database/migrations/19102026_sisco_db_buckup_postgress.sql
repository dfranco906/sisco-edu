-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-10-2026 a las 16:12:43
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sisco_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_docente`
--

CREATE TABLE `asignacion_docente` (
  `id_asignacion` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_grado` int(11) DEFAULT NULL,
  `carga_horaria` int(11) NOT NULL,
  `anio_lectivo` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `clave_asignacion_activa` tinyint(1) GENERATED ALWAYS AS (case when `activo` = 1 then 1 else NULL end) STORED
) ;

--
-- Volcado de datos para la tabla `asignacion_docente`
--

INSERT INTO `asignacion_docente` (`id_asignacion`, `id_profesor`, `id_materia`, `id_grado`, `carga_horaria`, `anio_lectivo`, `activo`) VALUES
(2, 1, 1, NULL, 5, 2026, 0),
(3, 1, 1, NULL, 5, 2026, 0),
(4, 8, 2, NULL, 8, 2026, 0),
(5, 9, 2, NULL, 8, 2026, 0),
(6, 1, 4, NULL, 8, 2026, 0),
(7, 8, 3, NULL, 8, 2026, 0),
(19, 23, 17, 15, 2, 2026, 1),
(20, 28, 13, 15, 3, 2026, 1),
(21, 30, 19, 18, 5, 2026, 1),
(22, 48, 15, 15, 1, 2026, 1),
(23, 21, 40, 18, 8, 2026, 1),
(24, 29, 31, 18, 3, 2026, 1),
(25, 32, 22, 18, 3, 2026, 1),
(26, 21, 26, 15, 2, 2026, 1),
(27, 22, 28, 18, 2, 2026, 1),
(28, 30, 16, 18, 4, 2026, 1),
(29, 25, 11, 18, 4, 2026, 1),
(30, 29, 8, 15, 2, 2026, 1),
(31, 31, 14, 18, 4, 2026, 1),
(32, 29, 8, 18, 5, 2026, 1),
(33, 21, 10, 18, 4, 2026, 1),
(34, 27, 33, 18, 2, 2026, 1),
(35, 24, 35, 18, 2, 2026, 1),
(38, 23, 17, 18, 2, 2026, 1),
(39, 56, 53, 18, 1, 2026, 1),
(47, 41, 57, 18, 3, 2026, 1),
(48, 41, 57, 17, 1, 2026, 1),
(49, 41, 57, 16, 1, 2026, 1),
(50, 41, 57, 13, 1, 2026, 1),
(51, 23, 20, 15, 4, 2026, 1),
(52, 41, 57, 14, 1, 2026, 1),
(53, 41, 57, 15, 1, 2026, 1),
(54, 21, 57, 12, 1, 2026, 1),
(55, 21, 57, 11, 1, 2026, 1),
(56, 21, 57, 10, 1, 2026, 1),
(57, 36, 12, 15, 2, 2026, 1),
(58, 32, 22, 15, 1, 2026, 1),
(59, 22, 18, 18, 3, 2026, 1),
(60, 23, 25, 15, 5, 2026, 1),
(61, 23, 23, 15, 6, 2026, 1),
(62, 27, 33, 15, 2, 2026, 1),
(63, 22, 28, 15, 2, 2026, 1),
(64, 22, 29, 15, 3, 2026, 1),
(65, 25, 11, 15, 4, 2026, 1),
(66, 63, 30, 15, 1, 2026, 1),
(83, 24, 35, 15, 2, 2026, 1),
(84, 32, 22, 14, 2, 2026, 1),
(85, 32, 22, 17, 2, 2026, 1),
(86, 41, 50, 17, 67, 2026, 0),
(87, 36, 12, 14, 2, 2026, 1),
(88, 23, 17, 14, 4, 2026, 1),
(89, 28, 13, 14, 3, 2026, 1),
(92, 48, 15, 14, 1, 2026, 1),
(93, 22, 18, 15, 2, 2026, 1),
(94, 45, 34, 14, 1, 2026, 1),
(95, 22, 32, 14, 2, 2026, 1),
(96, 25, 11, 14, 2, 2026, 1),
(100, 23, 23, 17, 3, 2026, 1),
(101, 29, 8, 14, 4, 2026, 1),
(102, 22, 28, 14, 2, 2026, 1),
(103, 27, 33, 14, 2, 2026, 1),
(104, 24, 35, 14, 2, 2026, 1),
(105, 23, 25, 14, 4, 2026, 1),
(106, 25, 68, 14, 2, 2026, 1),
(107, 29, 69, 14, 2, 2026, 1),
(108, 22, 19, 14, 2, 2026, 1),
(109, 36, 12, 17, 4, 2026, 1),
(110, 23, 17, 17, 2, 2026, 1),
(111, 28, 13, 17, 3, 2026, 1),
(112, 21, 43, 17, 6, 2026, 1),
(113, 34, 41, 17, 98, 2026, 0),
(114, 34, 41, 17, 4, 2026, 1),
(115, 30, 16, 17, 4, 2026, 1),
(116, 22, 70, 17, 2, 2026, 1),
(117, 22, 28, 17, 2, 2026, 1),
(118, 21, 40, 17, 3, 2026, 1),
(119, 29, 31, 17, 3, 2026, 1),
(120, 29, 8, 17, 2, 2026, 1),
(121, 21, 10, 17, 2, 2026, 1),
(122, 25, 11, 17, 3, 2026, 1),
(123, 63, 30, 14, 1, 2026, 1),
(124, 36, 12, 13, 2, 2026, 1),
(125, 32, 22, 13, 2, 2026, 1),
(126, 26, 19, 17, 1, 2026, 1),
(127, 27, 33, 13, 2, 2026, 1),
(128, 23, 25, 17, 3, 2026, 1),
(129, 23, 17, 13, 3, 2026, 1),
(130, 35, 14, 13, 4, 2026, 1),
(131, 22, 44, 13, 2, 2026, 1),
(132, 45, 34, 13, 2, 2026, 1),
(133, 21, 26, 14, 2, 2026, 1),
(134, 23, 23, 14, 3, 2026, 1),
(135, 45, 51, 13, 1, 2026, 1),
(136, 48, 15, 13, 1, 2026, 1),
(137, 29, 8, 13, 5, 2026, 1),
(138, 36, 12, 16, 4, 2026, 1),
(139, 32, 22, 16, 2, 2026, 1),
(140, 24, 35, 16, 1, 2026, 1),
(141, 30, 16, 16, 4, 2026, 1),
(142, 22, 44, 16, 2, 2026, 1),
(143, 56, 53, 13, 1, 2026, 1),
(144, 56, 53, 16, 1, 2026, 1),
(145, 56, 53, 14, 1, 2026, 1),
(146, 56, 53, 17, 1, 2026, 1),
(147, 23, 25, 16, 2, 2026, 1),
(148, 56, 53, 15, 1, 2026, 1),
(149, 23, 23, 16, 2, 2026, 1),
(150, 23, 17, 16, 2, 2026, 1),
(151, 29, 8, 16, 2, 2026, 1),
(152, 29, 47, 13, 4, 2026, 1),
(153, 28, 13, 16, 2, 2026, 1),
(154, 25, 11, 13, 3, 2026, 1),
(155, 25, 11, 16, 5, 2026, 1),
(156, 29, 31, 16, 3, 2026, 1),
(157, 39, 46, 13, 2, 2026, 1),
(158, 22, 68, 16, 2, 2026, 1),
(159, 39, 45, 13, 4, 2026, 1),
(160, 34, 27, 16, 4, 2026, 1),
(161, 22, 28, 13, 2, 2026, 1),
(162, 36, 49, 16, 2, 2026, 1),
(163, 21, 26, 13, 1, 2026, 1),
(164, 22, 28, 16, 2, 2026, 1),
(165, 24, 19, 13, 1, 2026, 1),
(166, 21, 40, 16, 4, 2026, 1),
(167, 63, 30, 13, 1, 2026, 1),
(168, 22, 28, 12, 3, 2026, 1),
(169, 33, 21, 12, 3, 2026, 1),
(170, 32, 22, 12, 2, 2026, 1),
(171, 22, 70, 14, 2, 2026, 1),
(172, 46, 71, 12, 3, 2026, 1),
(173, 27, 15, 12, 1, 2026, 1),
(174, 80, 36, 12, 6, 2026, 1),
(175, 27, 33, 12, 2, 2026, 1),
(176, 36, 12, 12, 4, 2026, 1),
(177, 22, 68, 12, 2, 2026, 1),
(178, 45, 34, 12, 1, 2026, 1),
(179, 42, 51, 12, 2, 2026, 1),
(180, 28, 13, 12, 2, 2026, 1),
(181, 21, 26, 12, 2, 2026, 1),
(182, 83, 8, 12, 5, 2026, 0),
(183, 63, 30, 12, 1, 2026, 1),
(184, 27, 50, 12, 2, 2026, 1),
(185, 22, 19, 12, 1, 2026, 1),
(186, 46, 71, 11, 3, 2026, 1),
(187, 22, 28, 11, 3, 2026, 1),
(188, 22, 19, 11, 1, 2026, 1),
(189, 33, 21, 11, 4, 2026, 1),
(190, 80, 36, 11, 6, 2026, 1),
(191, 27, 33, 11, 1, 2026, 1),
(192, 23, 10, 11, 2, 2026, 0),
(193, 48, 15, 11, 1, 2026, 1),
(194, 36, 12, 11, 4, 2026, 1),
(198, 28, 13, 13, 2, 2026, 1),
(199, 21, 26, 11, 2, 2026, 1),
(200, 83, 8, 11, 5, 2026, 1),
(201, 28, 13, 11, 2, 2026, 1),
(202, 45, 34, 11, 2, 2026, 1),
(206, 22, 68, 11, 2, 2026, 1),
(207, 42, 51, 11, 2, 2026, 1),
(208, 32, 22, 11, 2, 2026, 1),
(209, 22, 50, 11, 2, 2026, 1),
(210, 63, 30, 11, 1, 2026, 1),
(214, 26, 68, 10, 4, 2026, 1),
(215, 33, 21, 10, 4, 2026, 1),
(216, 46, 71, 10, 3, 2026, 1),
(217, 22, 28, 10, 3, 2026, 1),
(218, 36, 12, 10, 4, 2026, 1),
(219, 48, 15, 10, 1, 2026, 1),
(220, 27, 50, 10, 2, 2026, 1),
(221, 80, 36, 10, 5, 2026, 1),
(222, 27, 33, 10, 2, 2026, 1),
(223, 83, 8, 10, 5, 2026, 1),
(224, 21, 26, 10, 2, 2026, 1),
(225, 42, 51, 10, 2, 2026, 1),
(226, 45, 34, 10, 2, 2026, 1),
(227, 63, 30, 10, 1, 2026, 1),
(228, 32, 22, 10, 2, 2026, 1),
(229, 28, 13, 10, 2, 2026, 1),
(230, 83, 8, 12, 5, 2026, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id_asistencia` int(11) NOT NULL,
  `user_id_global` varchar(50) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `huella_id` int(11) DEFAULT NULL,
  `tipo_usuario` varchar(20) NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `estado` varchar(30) NOT NULL DEFAULT 'PRESENTE',
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias_estudiantes`
--

CREATE TABLE `asistencias_estudiantes` (
  `id_asistencia_estudiante` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `huella_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `estado` enum('PRESENTE','AUSENTE','TARDANZA') DEFAULT 'PRESENTE',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencias_estudiantes`
--

INSERT INTO `asistencias_estudiantes` (`id_asistencia_estudiante`, `id_estudiante`, `huella_id`, `fecha`, `hora`, `estado`, `activo`, `fecha_registro`) VALUES
(1, 2, 2, '2026-06-01', '14:54:35', 'TARDANZA', 0, '2026-06-01 12:54:23'),
(2, 7, 6, '2026-06-11', '07:30:00', 'TARDANZA', 0, '2026-06-11 16:57:08'),
(9, 85, 62, '2026-09-07', '14:01:15', 'PRESENTE', 1, '2026-09-07 17:01:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias_profesores`
--

CREATE TABLE `asistencias_profesores` (
  `id_asistencia_profesor` int(11) NOT NULL,
  `id_profesor` int(11) NOT NULL,
  `huella_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `estado` enum('PRESENTE','AUSENTE','TARDANZA') DEFAULT 'PRESENTE',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencias_profesores`
--

INSERT INTO `asistencias_profesores` (`id_asistencia_profesor`, `id_profesor`, `huella_id`, `fecha`, `hora`, `estado`, `activo`, `fecha_registro`) VALUES
(1, 1, 1, '2026-06-01', '14:56:37', 'TARDANZA', 0, '2026-06-01 12:55:47'),
(2, 7, 11, '2026-06-11', '07:00:00', 'PRESENTE', 0, '2026-06-11 17:04:43'),
(3, 21, 61, '2026-09-07', '13:59:16', 'PRESENTE', 1, '2026-09-07 16:59:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id_aula` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id_aula`, `nombre`, `codigo`, `ubicacion`, `activo`, `fecha_creacion`) VALUES
(1, 'Aula 1 Actualizada', 'AULA_1', 'Bloque B', 0, '2026-05-18 14:28:37'),
(3, 'Informatica', 'LAB_1', '2do piso', 0, '2026-06-11 17:08:48'),
(11, 'Sala de musica', 'MUS_1', '3er piso', 0, '2026-06-11 17:09:53'),
(12, 'Sala de musica Actualizada', 'MUS_2_ACTUALIZADA', '3er piso Actualizada', 0, '2026-06-11 17:17:39'),
(13, 'Sala de inglés Actualizada', 'ING_1_ACTUALIZADA', '1er piso Actualizada', 0, '2026-06-11 17:19:37'),
(14, 'Sala de robotica Actualizada 1', 'ROB_1_ACTUALIZADO_1', '2do piso Actualizado 1', 0, '2026-06-11 17:24:10'),
(15, 'Aula 2', 'AULA_2', '2do piso', 0, '2026-06-23 12:17:06'),
(16, 'Aula 3', 'AULA_03', '2do piso', 0, '2026-06-23 12:22:46'),
(17, 'Aula 4', 'AULA_04', '2do piso', 0, '2026-06-23 15:22:56'),
(18, 'AULA_A', 'AULA_A', '2do piso', 0, '2026-06-29 13:09:25'),
(22, 'SEPTIMO', '7_MO', '1° Piso', 1, '2026-08-20 16:14:58'),
(23, 'OCTAVO', '8_VO', '1° Piso', 1, '2026-08-20 16:15:25'),
(24, 'NOVENO', '9_NO', '1° Piso', 1, '2026-08-20 16:15:45'),
(25, '1° CURSO BCB', '1_RO_BCB', '1° Piso', 1, '2026-08-20 16:16:19'),
(26, '2° CURSO BCB', '2_DO_BCB', '2° piso', 1, '2026-08-20 16:16:55'),
(27, '3° CURSO BCB', '3_RO_BCB', '2° Piso', 1, '2026-08-20 16:17:21'),
(28, '1° CURSO BTI', '1_RO_BTI', '1° Piso', 1, '2026-08-20 16:18:03'),
(29, '2° CURSO BTI', '2_DO_BTI', '2° Piso', 1, '2026-08-20 16:18:34'),
(30, '3° CURSO BTI', '3_RO_BTI', '2° Piso', 1, '2026-08-20 16:18:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aula_huellas_sync`
--

CREATE TABLE `aula_huellas_sync` (
  `id_aula_sync` int(11) NOT NULL,
  `id_sync` int(11) NOT NULL,
  `id_huella` int(11) NOT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `ci` varchar(30) NOT NULL,
  `slot_local` int(11) NOT NULL,
  `tipo_persona` varchar(20) NOT NULL,
  `estado` enum('RECIBIDO','ERROR') DEFAULT 'RECIBIDO',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aula_huellas_sync`
--

INSERT INTO `aula_huellas_sync` (`id_aula_sync`, `id_sync`, `id_huella`, `id_aula`, `room_id`, `ci`, `slot_local`, `tipo_persona`, `estado`, `fecha_registro`) VALUES
(2, 50, 50, 18, NULL, '3216543', 1, 'estudiante', 'RECIBIDO', '2026-08-13 18:02:12'),
(3, 51, 51, 18, NULL, '3216543', 1, 'estudiante', 'RECIBIDO', '2026-08-13 18:04:18'),
(4, 52, 52, 18, NULL, '3216543', 1, 'estudiante', 'RECIBIDO', '2026-08-17 12:26:35'),
(5, 53, 53, 18, NULL, '3216543', 1, 'estudiante', 'RECIBIDO', '2026-08-17 14:09:16'),
(6, 54, 54, 18, NULL, '3216543', 1, 'estudiante', 'RECIBIDO', '2026-08-17 14:23:41'),
(7, 56, 56, 18, NULL, '6324185', 2, 'estudiante', 'RECIBIDO', '2026-08-17 14:25:18'),
(8, 57, 57, 18, NULL, '3554612', 3, 'estudiante', 'RECIBIDO', '2026-08-17 14:33:20'),
(9, 58, 58, 30, NULL, '6767676', 4, 'profesor', 'RECIBIDO', '2026-09-07 14:53:53'),
(10, 61, 61, 30, NULL, '6767676', 4, 'profesor', 'RECIBIDO', '2026-09-07 16:17:04'),
(11, 62, 62, 30, NULL, '5463214', 5, 'estudiante', 'RECIBIDO', '2026-09-07 17:01:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `biometric_mapping`
--

CREATE TABLE `biometric_mapping` (
  `id_mapping` int(11) NOT NULL,
  `user_id_global` varchar(50) NOT NULL,
  `room_id` varchar(50) NOT NULL,
  `sensor_slot` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clases_diarias`
--

CREATE TABLE `clases_diarias` (
  `id_clase` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `id_horario` int(11) DEFAULT NULL,
  `id_plan` int(11) DEFAULT NULL,
  `id_unidad` int(11) DEFAULT NULL,
  `id_capacidad` int(11) DEFAULT NULL,
  `id_tema` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `clases_diarias`
--

INSERT INTO `clases_diarias` (`id_clase`, `id_asignacion`, `id_horario`, `id_plan`, `id_unidad`, `id_capacidad`, `id_tema`, `fecha`, `hora_inicio`, `hora_fin`, `created_at`, `updated_at`) VALUES
(29, 23, 48, 8, 17, 17, 19, '2026-09-07', '13:30:00', '14:10:00', '2026-09-07 16:59:16', '2026-09-07 16:59:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clase_diaria_indicadores`
--

CREATE TABLE `clase_diaria_indicadores` (
  `id_clase` int(11) NOT NULL,
  `id_indicador` int(11) NOT NULL,
  `cumplido` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clase_diaria_indicadores`
--

INSERT INTO `clase_diaria_indicadores` (`id_clase`, `id_indicador`, `cumplido`, `observaciones`) VALUES
(29, 29, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_informes`
--

CREATE TABLE `configuracion_informes` (
  `id_configuracion` int(11) NOT NULL,
  `membrete_path` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion_informes`
--

INSERT INTO `configuracion_informes` (`id_configuracion`, `membrete_path`, `activo`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'public/uploads/informes/membrete_20260831_163520_ee63b19c55e9.png', 0, 29, '2026-08-31 14:35:20', '2026-08-31 14:35:29'),
(2, 'public/uploads/informes/membrete_20260831_163529_3d8d3fc0c96d.png', 0, 29, '2026-08-31 14:35:29', '2026-08-31 14:35:57'),
(3, 'public/uploads/informes/membrete_20260831_163557_f52045138525.png', 0, 29, '2026-08-31 14:35:57', '2026-08-31 14:35:58'),
(4, 'public/uploads/informes/membrete_20260831_163558_01354af7000b.png', 0, 29, '2026-08-31 14:35:58', '2026-08-31 14:37:18'),
(5, 'public/uploads/informes/membrete_20260831_163718_836c8461cb11.jpg', 0, 29, '2026-08-31 14:37:18', '2026-08-31 14:37:27'),
(6, 'public/uploads/informes/membrete_20260831_163727_17ba334b0e17.png', 0, 29, '2026-08-31 14:37:27', '2026-08-31 14:37:34'),
(7, 'public/uploads/informes/membrete_20260831_163734_180aed68ab69.png', 0, 29, '2026-08-31 14:37:34', '2026-08-31 14:37:36'),
(8, 'public/uploads/informes/membrete_20260831_163736_f4595b46b6f0.png', 0, 29, '2026-08-31 14:37:36', '2026-08-31 14:37:36'),
(9, 'public/uploads/informes/membrete_20260831_163736_ae8ba3d2baba.png', 0, 29, '2026-08-31 14:37:36', '2026-08-31 14:38:34'),
(10, 'public/uploads/informes/membrete_20260831_163834_a3e3e99dec37.png', 0, 29, '2026-08-31 14:38:34', '2026-08-31 14:38:34'),
(11, 'public/uploads/informes/membrete_20260831_163834_38d8c5866c55.png', 0, 29, '2026-08-31 14:38:34', '2026-08-31 14:38:41'),
(12, 'public/uploads/informes/membrete_20260831_163841_db7504e57b67.png', 0, 29, '2026-08-31 14:38:41', '2026-08-31 14:41:54'),
(13, 'public/uploads/informes/membrete_20260831_164154_b0ec0c293d15.png', 0, 29, '2026-08-31 14:41:54', '2026-08-31 15:06:39'),
(14, 'public/uploads/informes/membrete_20260831_170639_d3e1d6b2e0bb.png', 1, 29, '2026-08-31 15:06:39', '2026-08-31 15:06:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id_estudiante` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula_identidad` varchar(20) NOT NULL,
  `id_grado` int(11) DEFAULT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `huella_id` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `user_id_global` varchar(50) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `fingerprint_data_user` longtext DEFAULT NULL,
  `pendiente_sync` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id_estudiante`, `nombre`, `apellido`, `cedula_identidad`, `id_grado`, `room_id`, `huella_id`, `fecha_registro`, `activo`, `user_id_global`, `id_aula`, `fingerprint_data_user`, `pendiente_sync`) VALUES
(2, 'Carlos Actualizado', 'Gomez Actualizado', '5555555', NULL, 'AULA_1', 12, '2026-05-21 17:05:33', 0, 'EST_2', NULL, NULL, 1),
(3, 'Tiago Ortega', 'PERALTA BARUJA', '1234565', NULL, NULL, 4, '2026-06-01 17:33:58', 0, 'EST_3', NULL, 'ef01ffffffff02008203035326000120016b0000000000000000000000000000000000000000000000000000000000000000000000000000000b0008006f000c0000c3333cfcffeffbbeeeebaaaaaa666996669554451551104040000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000fb9ef01ffffffff0200824d97185e1a9b811e5aa381be3d2c82de592c82fe3a1dc1df22a3021f3aa381ff25a6d7df102e015f41b1831f40b843df2fb519bc39a7829d1cad821d1f3155fd2cb1c35d58be987b2a98809810b441581634d7d855bbc7b835bdd9182e415839301557b63696d776163781164d3d1b3628c140165f9ad8771a39c4171c3b59b73613ef01ffffffff02008247bd1977273f991737994114503f5e143c3e5835524064f500000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000846ef01ffffffff02008203035e32000120016800000000000000000000000000000000000000000000000000000000000000000000000000000009000c00650000030030ccccff3fefbfbbaeaaa9a99a6665aa955440544444000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000e4eef01ffffffff0200821ba2485e2f25897e1ba9c9be25205f7f62249e5f3ea9c85f27abdf5f63ae41ba5d36db5a25b7c9fa39255f7b62a8dfbb4a23875809aa4c58432e5f783e309e384c3f1cb846a49f194c31c3b951b81c5930b85ad950bec9590a2f63f6483206763e329bb653b64536303a43760baccc173eb69b172e3b9ab73f230934492847943467ef01ffffffff0200825e2b1e14312fde5456b205346232443432ad1f75342d9e15122ea3b51330ca9558b0dc352f3143d554b35ad560335c7513ad8bd31f325cd342b8457355a7dfb0493ac5b04e2c5e7100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001d26ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff08', 1),
(5, 'Nahuel', 'Montiel', '1234568', NULL, NULL, 99, '2026-06-01 17:51:10', 0, 'EST_5', NULL, NULL, 0),
(6, 'Danilo', 'Franco', '5356093', NULL, NULL, 17, '2026-06-08 15:01:19', 0, 'EST_6', NULL, 'ef01ffffffff02008203035b1d00012001880000000000000000000000000000000000000000000000000000000000000000000000000000000f00010077000000033033ffffffffffffffeeaaaaaaaaaa665655555555515515110040000000000000000000000000000000000000000000000000000000000000000000000000000000000000000010eeef01ffffffff0200823690c67e6d9760fe33991bfe2f1e46de42a2c51e542c1abe39aedc9e5c33eb3e4442025e2713c59f5722a9df682625ff29bc5b7f262e9d1c26ab061d73b2289a773313fa3e3b447b5a8c4798683214585f0c5cd969306879413d9b795e8fde165b9088b75a171e5760981fd55a1a1f135b1be2930000000000000000000000002f30ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008203036315000120018d0000000000000000000000000000000000000000000000000000000000000000000000000000000b00010077000000000300cfffffffffffffffeeaaaaaaaaa659595595559555515144400000000000000000000000000000000000000000000000000000000000000000000000000000000000000000123cef01ffffffff0200825d88699e3089c4fe33229a7e6aad009e4331c25e68bac0be5abc027e4589841f6c0ca71f5b10d8df41965a5f6017abbf333442bf594042df2e1603fa2f18da9b6d9540d96f16183946a1c33949a39a194b26015900000000000000000000000000000000000000000000000000000000000000000000000000000000000000002376ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff02008200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000084ef01ffffffff08', 1),
(7, 'Fiorella', 'Martinez', '1111111', NULL, NULL, 6, '2026-06-08 16:12:45', 0, 'EST_7', NULL, NULL, 1),
(8, 'Fiorella Actualizado', 'Galarza Actualizado', '1478526 Actualizado', NULL, NULL, NULL, '2026-06-08 16:44:55', 0, 'EST_8', NULL, NULL, 0),
(9, 'Fiorella', 'Galarza', '1234569', NULL, NULL, 15, '2026-06-08 17:08:51', 0, 'EST_9', NULL, NULL, 1),
(10, 'Prueba1', 'Crear', '1236547', NULL, NULL, NULL, '2026-06-08 17:30:29', 0, 'EST_10', NULL, NULL, 0),
(11, 'Pepa', 'Peron', '00001234', NULL, NULL, NULL, '2026-06-23 11:50:37', 0, 'EST_11', NULL, NULL, 0),
(19, 'Test', 'Prueba', '999999', 1, 'AULA_1', NULL, '2026-06-23 12:03:56', 0, 'EST_19', NULL, NULL, 0),
(21, 'Pepa', 'Montiel', '00001235', 2, 'LAB_1', NULL, '2026-06-23 12:04:35', 0, 'EST_21', NULL, NULL, 0),
(22, 'Pepa', 'Montiel', '00001237', 4, 'AULA_3', NULL, '2026-06-23 12:23:31', 0, 'EST_22', NULL, NULL, 0),
(23, 'Pepa', 'Montiel', '00001239', 5, 'AULA_A', 33, '2026-06-23 15:23:42', 0, 'EST_23', NULL, NULL, 1),
(24, 'Nahuel', 'Montiel', '3216548', 3, 'AULA_2', NULL, '2026-06-29 14:25:49', 0, 'EST_24', NULL, NULL, 0),
(25, 'Nahuel', 'Montiel', '3216543', 6, 'AULA_A', 42, '2026-06-29 16:16:54', 0, 'EST_25', NULL, NULL, 1),
(26, 'Pepa', 'Tronquiel', '676785', 1, NULL, NULL, '2026-08-13 16:28:23', 0, 'EST_6a7df0a7d4232_493', NULL, NULL, 0),
(27, 'Nahuel', 'Montaner', '9876542', 6, NULL, NULL, '2026-08-13 16:44:13', 0, 'EST_6a7df45d67aa3_736', NULL, NULL, 0),
(28, 'Cristhyan', 'Pintos', '6324185', 6, NULL, NULL, '2026-08-17 14:23:09', 0, 'EST_6a83194d6f4be_213', NULL, NULL, 0),
(29, 'Matias', 'Velazquez', '3554612', 6, NULL, NULL, '2026-08-17 14:32:49', 0, 'EST_6a831b915dfa6_218', NULL, NULL, 0),
(81, 'Thiago', 'Amarilla', '6548971', 18, NULL, NULL, '2026-08-31 14:58:02', 1, 'EST_6a95967aadefb_497', NULL, NULL, 0),
(82, 'Fiorella', 'Martinez', '6543218', 18, NULL, NULL, '2026-08-31 14:58:20', 1, 'EST_6a95968c56312_300', NULL, NULL, 0),
(83, 'Matias', 'Velazquez', '9875213', 18, NULL, NULL, '2026-08-31 14:58:44', 1, 'EST_6a9596a428b03_722', NULL, NULL, 0),
(84, 'Tiago', 'Ortega', '6306661', 18, NULL, NULL, '2026-08-31 14:59:02', 1, 'EST_6a9596b64fc37_659', NULL, NULL, 0),
(85, 'Thiago', 'Zarate', '5463214', 18, NULL, NULL, '2026-08-31 14:59:19', 1, 'EST_6a9596c7e0255_512', NULL, NULL, 0),
(86, 'Nahuel', 'Montiel', '6541232', 18, NULL, NULL, '2026-08-31 14:59:54', 1, 'EST_6a9596eaa760b_305', NULL, NULL, 0),
(87, 'Florencia', 'Lopez', '6532145', 18, NULL, NULL, '2026-08-31 15:00:10', 1, 'EST_6a9596faebdc2_268', NULL, NULL, 0),
(88, 'Arturo', 'Rubio', '65421345', 18, NULL, NULL, '2026-08-31 15:00:24', 1, 'EST_6a959708eb7c1_776', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_asistencia`
--

CREATE TABLE `eventos_asistencia` (
  `id_evento` int(11) NOT NULL,
  `user_id_global` varchar(50) NOT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'PRESENTE',
  `timestamp_evento` datetime NOT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `origen_node_id` varchar(100) DEFAULT NULL,
  `sincronizado` tinyint(1) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_recibido` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos_asistencia`
--

INSERT INTO `eventos_asistencia` (`id_evento`, `user_id_global`, `id_aula`, `estado`, `timestamp_evento`, `room_id`, `origen_node_id`, `sincronizado`, `activo`, `fecha_recibido`) VALUES
(1, 'USER_001', 1, 'PRESENTE', '2026-05-21 15:20:33', 'AULA_1', 'ESP32_AULA_1', 1, 0, '2026-05-21 18:09:56'),
(2, 'EST_6a83194d6f4be_213', 18, 'PRESENTE', '2026-08-17 16:25:42', NULL, 'GATEWAY_ESP32', 1, 1, '2026-08-17 14:25:42'),
(3, 'EST_6a831b915dfa6_218', 18, 'PRESENTE', '2026-08-17 16:33:24', NULL, 'GATEWAY_ESP32', 1, 1, '2026-08-17 14:33:24'),
(4, 'EST_6a83194d6f4be_213', 18, 'PRESENTE', '2026-08-17 16:59:01', NULL, 'GATEWAY_ESP32', 1, 1, '2026-08-17 14:59:01'),
(20, 'PROF_6a872970109a6_428', 30, 'PRESENTE', '2026-09-07 18:17:39', NULL, 'GATEWAY_ESP32', 1, 1, '2026-09-07 16:17:39'),
(27, 'PROF_6a872970109a6_428', 30, 'PRESENTE', '2026-09-07 12:39:05', NULL, 'GATEWAY_ESP32', 1, 1, '2026-09-07 16:39:05'),
(31, 'PROF_6a872970109a6_428', 30, 'PRESENTE', '2026-09-07 13:59:16', NULL, 'GATEWAY_ESP32', 1, 1, '2026-09-07 16:59:16'),
(32, 'EST_6a9596c7e0255_512', 30, 'PRESENTE', '2026-09-07 14:01:15', NULL, 'GATEWAY_ESP32', 1, 1, '2026-09-07 17:01:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grados`
--

CREATE TABLE `grados` (
  `id_grado` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_aula` int(11) NOT NULL,
  `room_id` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grados`
--

INSERT INTO `grados` (`id_grado`, `nombre`, `id_aula`, `room_id`, `activo`, `fecha_registro`) VALUES
(1, '3ro BTI', 1, 'AULA_1', 0, '2026-06-23 11:36:16'),
(2, '2do Informática', 3, 'LAB_1', 0, '2026-06-23 11:36:16'),
(3, '8vo A', 15, 'AULA_2', 0, '2026-06-23 12:17:31'),
(4, '7mo A', 16, 'AULA_3', 0, '2026-06-23 12:23:03'),
(5, '8vo B', 17, 'AULA_4', 0, '2026-06-23 15:23:15'),
(6, '9no A', 18, 'AULA_A', 0, '2026-06-29 16:16:38'),
(10, '7°', 22, '7_MO', 1, '2026-08-20 16:15:56'),
(11, '8°', 23, '8_VO', 1, '2026-08-20 16:16:04'),
(12, '9°', 24, '9_NO', 1, '2026-08-20 16:16:12'),
(13, '1° BCB', 25, '1_RO_BCB', 1, '2026-08-20 16:16:35'),
(14, '2° BCB', 26, '2_DO_BCB', 1, '2026-08-20 16:18:03'),
(15, '3° BCB', 27, '3_RO_BCB', 1, '2026-08-20 16:18:44'),
(16, '1° BTI', 28, '1_RO_BTI', 1, '2026-08-20 16:19:05'),
(17, '2° BTI', 29, '2_DO_BTI', 1, '2026-08-20 16:19:15'),
(18, '3° BTI', 30, '3_RO_BTI', 1, '2026-08-20 16:19:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id_horario` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `id_grado` int(11) DEFAULT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `grado` varchar(50) DEFAULT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `permite_superposicion` tinyint(1) NOT NULL DEFAULT 0,
  `id_horario_vinculado` int(11) DEFAULT NULL,
  `id_grupo_clase_conjunta` int(11) DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id_horario`, `id_asignacion`, `id_grado`, `id_aula`, `grado`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`, `activo`, `permite_superposicion`, `id_horario_vinculado`, `id_grupo_clase_conjunta`) VALUES
(31, 23, 18, 30, NULL, 'Lunes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(32, 21, 18, 30, NULL, 'Lunes', '07:00:00', '07:40:00', NULL, 1, 0, NULL, NULL),
(33, 33, 18, 30, NULL, 'Martes', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(34, 28, 18, 30, NULL, 'Martes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(35, 28, 18, 30, NULL, 'Martes', '12:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(36, 31, 18, 30, NULL, 'Miércoles', '07:00:00', '07:40:00', NULL, 1, 0, NULL, NULL),
(37, 30, 15, 27, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 1, 109, 37),
(40, 21, 18, 30, NULL, 'Lunes', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(41, 21, 18, 30, NULL, 'Lunes', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(42, 23, 18, 30, NULL, 'Lunes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(43, 39, 18, 30, NULL, 'Lunes', '10:20:00', '10:50:00', NULL, 1, 1, 189, 43),
(44, 23, 18, 30, NULL, 'Lunes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(45, 23, 18, 30, NULL, 'Lunes', '11:30:00', '12:10:00', NULL, 0, 0, NULL, NULL),
(46, 39, 18, 30, NULL, 'Lunes', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(47, 23, 18, 30, NULL, 'Lunes', '12:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(48, 23, 18, 30, NULL, 'Lunes', '13:30:00', '14:10:00', NULL, 1, 0, NULL, NULL),
(49, 23, 18, 30, NULL, 'Lunes', '14:10:00', '14:50:00', NULL, 1, 0, NULL, NULL),
(50, 23, 18, 30, NULL, 'Lunes', '14:50:00', '15:30:00', NULL, 1, 0, NULL, NULL),
(58, 39, 18, 30, NULL, 'Martes', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(59, 33, 18, 30, NULL, 'Martes', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(60, 31, 18, 30, NULL, 'Miércoles', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(61, 33, 18, 30, NULL, 'Martes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(62, 39, 18, 30, NULL, 'Martes', '10:20:00', '10:50:00', NULL, 1, 1, 190, 62),
(63, 27, 18, 30, NULL, 'Jueves', '07:00:00', '07:40:00', NULL, 1, 1, 134, 63),
(64, 27, 18, 30, NULL, 'Jueves', '07:40:00', '08:20:00', NULL, 1, 1, 135, 64),
(65, 28, 18, 30, NULL, 'Martes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(66, 28, 18, 30, NULL, 'Martes', '13:30:00', '14:10:00', NULL, 1, 0, NULL, NULL),
(67, 21, 18, 30, NULL, 'Martes', '14:10:00', '14:50:00', NULL, 1, 0, NULL, NULL),
(68, 21, 18, 30, NULL, 'Martes', '14:50:00', '15:30:00', NULL, 1, 0, NULL, NULL),
(69, 47, 18, 30, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 108, 69),
(70, 35, 18, 30, NULL, 'Jueves', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(71, 56, 10, 22, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 149, 71),
(72, 35, 18, 30, NULL, 'Jueves', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(73, 38, 18, 30, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(74, 34, 18, 30, NULL, 'Jueves', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(75, 39, 18, 30, NULL, 'Jueves', '10:20:00', '10:50:00', NULL, 1, 1, 192, 75),
(76, 38, 18, 30, NULL, 'Viernes', '08:20:00', '09:00:00', NULL, 0, 0, NULL, NULL),
(77, 29, 18, 30, NULL, 'Jueves', '10:50:00', '11:30:00', NULL, 1, 1, 136, 77),
(78, 29, 18, 30, NULL, 'Jueves', '11:30:00', '12:10:00', NULL, 1, 1, 137, 78),
(79, 39, 18, 30, NULL, 'Jueves', '12:10:00', '12:50:00', NULL, 1, 1, 390, 79),
(80, 30, 15, 27, NULL, 'Viernes', '08:20:00', '09:00:00', NULL, 1, 1, 110, 80),
(81, 23, 18, 30, NULL, 'Jueves', '12:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(82, 23, 18, 30, NULL, 'Jueves', '13:30:00', '14:10:00', NULL, 1, 0, NULL, NULL),
(83, 23, 18, 30, NULL, 'Jueves', '14:10:00', '14:50:00', NULL, 1, 0, NULL, NULL),
(84, 23, 18, 30, NULL, 'Jueves', '14:50:00', '15:30:00', NULL, 1, 0, NULL, NULL),
(85, 25, 18, 30, NULL, 'Viernes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(86, 25, 18, 30, NULL, 'Viernes', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(87, 39, 18, 30, NULL, 'Viernes', '12:50:00', '13:30:00', NULL, 1, 1, 343, 87),
(88, 34, 18, 30, NULL, 'Miércoles', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(89, 39, 18, 30, NULL, 'Miércoles', '10:20:00', '10:50:00', NULL, 1, 1, 191, 89),
(90, 31, 18, 30, NULL, 'Miércoles', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(91, 31, 18, 30, NULL, 'Miércoles', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(92, 29, 18, 30, NULL, 'Miércoles', '12:10:00', '12:50:00', NULL, 1, 1, 129, 92),
(93, 29, 18, 30, NULL, 'Miércoles', '12:50:00', '13:30:00', NULL, 1, 1, 130, 93),
(94, 51, 15, 27, NULL, 'Lunes', '07:00:00', '07:40:00', NULL, 1, 0, NULL, NULL),
(95, 51, 15, 27, NULL, 'Lunes', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(99, 51, 15, 27, NULL, 'Lunes', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(100, 51, 15, 27, NULL, 'Lunes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(101, 57, 15, 27, NULL, 'Lunes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(102, 57, 15, 27, NULL, 'Lunes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(103, 58, 15, 27, NULL, 'Lunes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(104, 60, 15, 27, NULL, 'Lunes', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(105, 60, 15, 27, NULL, 'Lunes', '12:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(108, 53, 15, 27, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 69, 69),
(109, 32, 18, 30, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 1, 37, 37),
(110, 32, 18, 30, NULL, 'Viernes', '08:20:00', '09:00:00', NULL, 1, 1, 80, 80),
(118, 61, 15, 27, NULL, 'Martes', '07:40:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(119, 20, 15, 27, NULL, 'Martes', '09:00:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(120, 22, 15, 27, NULL, 'Martes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(121, 20, 15, 27, NULL, 'Martes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(122, 26, 15, 27, NULL, 'Martes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(126, 61, 15, 27, NULL, 'Miércoles', '07:00:00', '08:00:00', NULL, 1, 0, NULL, NULL),
(127, 65, 15, 27, NULL, 'Miércoles', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(128, 62, 15, 27, NULL, 'Miércoles', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(129, 65, 15, 27, NULL, 'Miércoles', '12:10:00', '12:50:00', NULL, 1, 1, 92, 92),
(130, 65, 15, 27, NULL, 'Miércoles', '12:50:00', '13:30:00', NULL, 1, 1, 93, 93),
(134, 63, 15, 27, NULL, 'Jueves', '07:00:00', '07:40:00', NULL, 1, 1, 63, 63),
(135, 63, 15, 27, NULL, 'Jueves', '07:40:00', '08:20:00', NULL, 1, 1, 64, 64),
(136, 65, 15, 27, NULL, 'Jueves', '10:50:00', '11:30:00', NULL, 1, 1, 77, 77),
(137, 65, 15, 27, NULL, 'Jueves', '11:30:00', '12:10:00', NULL, 1, 1, 78, 78),
(141, 59, 18, 30, NULL, 'Viernes', '09:00:00', '10:20:00', NULL, 1, 1, 142, 141),
(142, 93, 15, 27, NULL, 'Viernes', '09:00:00', '10:20:00', NULL, 1, 1, 141, 141),
(143, 59, 18, 30, NULL, 'Viernes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(144, 24, 18, 30, NULL, 'Viernes', '13:30:00', '15:30:00', NULL, 1, 0, NULL, NULL),
(149, 55, 11, 23, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 71, 71),
(150, 54, 12, 24, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 71, 71),
(151, 61, 15, 27, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(152, 64, 15, 27, NULL, 'Jueves', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(153, 84, 14, 26, NULL, 'Lunes', '07:00:00', '07:40:00', NULL, 1, 1, 154, 153),
(154, 85, 17, 29, NULL, 'Lunes', '07:00:00', '07:40:00', NULL, 1, 1, 153, 153),
(155, 84, 14, 26, NULL, 'Lunes', '07:40:00', '08:20:00', NULL, 1, 1, 156, 155),
(156, 85, 17, 29, NULL, 'Lunes', '07:40:00', '08:20:00', NULL, 1, 1, 155, 155),
(157, 87, 14, 26, NULL, 'Lunes', '08:20:00', '09:40:00', NULL, 1, 1, 179, 157),
(158, 64, 15, 27, NULL, 'Jueves', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(159, 83, 15, 27, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(160, 89, 14, 26, NULL, 'Lunes', '11:30:00', '13:30:00', NULL, 1, 1, 256, 160),
(161, 92, 14, 26, NULL, 'Martes', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(162, 95, 14, 26, NULL, 'Martes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(163, 95, 14, 26, NULL, 'Martes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(164, 94, 14, 26, NULL, 'Martes', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(165, 60, 15, 27, NULL, 'Viernes', '10:50:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(166, 66, 15, 27, NULL, 'Viernes', '12:50:00', '13:30:00', NULL, 1, 1, 285, 166),
(167, 102, 14, 26, NULL, 'Martes', '12:10:00', '12:50:00', NULL, 1, 1, 248, 167),
(168, 102, 14, 26, NULL, 'Martes', '12:50:00', '13:30:00', NULL, 1, 1, 249, 168),
(169, 96, 14, 26, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 0, 0, NULL, NULL),
(170, 88, 14, 26, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(171, 96, 14, 26, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 1, 250, 171),
(172, 101, 14, 26, NULL, 'Miércoles', '12:50:00', '13:30:00', NULL, 1, 1, 251, 172),
(173, 103, 14, 26, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(174, 96, 14, 26, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 1, 253, 174),
(175, 104, 14, 26, NULL, 'Jueves', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(176, 105, 14, 26, NULL, 'Jueves', '10:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(177, 101, 14, 26, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 1, 182, 177),
(178, 104, 14, 26, NULL, 'Viernes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(179, 109, 17, 29, NULL, 'Lunes', '08:20:00', '09:40:00', NULL, 1, 1, 157, 157),
(180, 107, 14, 26, NULL, 'Viernes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(181, 108, 14, 26, NULL, 'Viernes', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(182, 120, 17, 29, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 1, 177, 177),
(183, 128, 17, 29, NULL, 'Viernes', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(184, 100, 17, 29, NULL, 'Viernes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(185, 114, 17, 29, NULL, 'Viernes', '10:50:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(186, 128, 17, 29, NULL, 'Viernes', '13:30:00', '14:50:00', NULL, 1, 0, NULL, NULL),
(187, 100, 17, 29, NULL, 'Viernes', '14:50:00', '16:00:00', NULL, 1, 0, NULL, NULL),
(188, 39, 18, 30, NULL, 'Viernes', '10:20:00', '10:50:00', NULL, 1, 1, 193, 188),
(189, 148, 15, 27, NULL, 'Lunes', '10:20:00', '10:50:00', NULL, 1, 1, 43, 43),
(190, 148, 15, 27, NULL, 'Martes', '10:20:00', '10:50:00', NULL, 1, 1, 62, 62),
(191, 148, 15, 27, NULL, 'Miércoles', '10:20:00', '10:50:00', NULL, 1, 1, 89, 89),
(192, 148, 15, 27, NULL, 'Jueves', '10:20:00', '10:50:00', NULL, 1, 1, 75, 75),
(193, 148, 15, 27, NULL, 'Viernes', '10:20:00', '10:50:00', NULL, 1, 1, 188, 188),
(194, 112, 17, 29, NULL, 'Lunes', '13:30:00', '14:50:00', NULL, 1, 0, NULL, NULL),
(195, 114, 17, 29, NULL, 'Lunes', '14:50:00', '16:00:00', NULL, 1, 0, NULL, NULL),
(196, 115, 17, 29, NULL, 'Martes', '07:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(197, 124, 13, 25, NULL, 'Lunes', '07:00:00', '08:20:00', NULL, 1, 1, 198, 197),
(198, 138, 16, 28, NULL, 'Lunes', '07:00:00', '08:20:00', NULL, 1, 1, 197, 197),
(199, 125, 13, 25, NULL, 'Lunes', '08:20:00', '09:40:00', NULL, 1, 1, 200, 199),
(200, 139, 16, 28, NULL, 'Lunes', '08:20:00', '09:40:00', NULL, 1, 1, 199, 199),
(201, 133, 14, 26, NULL, 'Martes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(202, 116, 17, 29, NULL, 'Martes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(203, 127, 13, 25, NULL, 'Lunes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(204, 127, 13, 25, NULL, 'Lunes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(205, 129, 13, 25, NULL, 'Lunes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(206, 141, 16, 28, NULL, 'Lunes', '10:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(207, 130, 13, 25, NULL, 'Lunes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(208, 131, 13, 25, NULL, 'Martes', '07:40:00', '09:00:00', NULL, 1, 1, 209, 208),
(209, 142, 16, 28, NULL, 'Martes', '07:40:00', '09:00:00', NULL, 1, 1, 208, 208),
(210, 132, 13, 25, NULL, 'Martes', '09:00:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(211, 135, 13, 25, NULL, 'Martes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(212, 136, 13, 25, NULL, 'Martes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(213, 129, 13, 25, NULL, 'Martes', '12:10:00', '13:30:00', NULL, 1, 1, 214, 213),
(214, 150, 16, 28, NULL, 'Martes', '12:10:00', '13:30:00', NULL, 1, 1, 213, 213),
(215, 168, 12, 24, NULL, 'Lunes', '07:00:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(216, 169, 12, 24, NULL, 'Lunes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(221, 170, 12, 24, NULL, 'Lunes', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(222, 172, 12, 24, NULL, 'Lunes', '11:30:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(223, 169, 12, 24, NULL, 'Martes', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(224, 173, 12, 24, NULL, 'Martes', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(225, 174, 12, 24, NULL, 'Martes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(226, 137, 13, 25, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 1, 227, 226),
(227, 151, 16, 28, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 1, 226, 226),
(228, 198, 13, 25, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 1, 229, 228),
(229, 153, 16, 28, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 1, 228, 228),
(230, 137, 13, 25, NULL, 'Miércoles', '09:40:00', '10:20:00', NULL, 1, 1, 231, 230),
(231, 151, 16, 28, NULL, 'Miércoles', '09:40:00', '10:20:00', NULL, 1, 1, 230, 230),
(232, 152, 13, 25, NULL, 'Miércoles', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(233, 130, 13, 25, NULL, 'Miércoles', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(234, 174, 12, 24, NULL, 'Martes', '10:10:00', '10:50:00', NULL, 1, 0, NULL, NULL),
(235, 175, 12, 24, NULL, 'Martes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(236, 176, 12, 24, NULL, 'Martes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(237, 176, 12, 24, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(238, 169, 12, 24, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(239, 154, 13, 25, NULL, 'Jueves', '09:40:00', '10:20:00', NULL, 1, 1, 240, 239),
(240, 155, 16, 28, NULL, 'Jueves', '09:40:00', '10:20:00', NULL, 1, 1, 239, 239),
(241, 161, 13, 25, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 1, 242, 241),
(242, 164, 16, 28, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 1, 241, 241),
(243, 163, 13, 25, NULL, 'Viernes', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(244, 137, 13, 25, NULL, 'Viernes', '09:00:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(245, 160, 16, 28, NULL, 'Viernes', '09:00:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(246, 88, 14, 26, NULL, 'Lunes', '09:40:00', '10:20:00', NULL, 1, 1, 254, 246),
(247, 88, 14, 26, NULL, 'Lunes', '10:50:00', '11:30:00', NULL, 1, 1, 255, 247),
(248, 117, 17, 29, NULL, 'Martes', '12:10:00', '12:50:00', NULL, 1, 1, 167, 167),
(249, 117, 17, 29, NULL, 'Martes', '12:50:00', '13:30:00', NULL, 1, 1, 168, 168),
(250, 122, 17, 29, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 1, 171, 171),
(251, 120, 17, 29, NULL, 'Miércoles', '12:50:00', '13:30:00', NULL, 1, 1, 172, 172),
(252, 101, 14, 26, NULL, 'Miércoles', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(253, 122, 17, 29, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 1, 174, 174),
(254, 110, 17, 29, NULL, 'Lunes', '09:40:00', '10:20:00', NULL, 1, 1, 246, 246),
(255, 110, 17, 29, NULL, 'Lunes', '10:50:00', '11:30:00', NULL, 1, 1, 247, 247),
(256, 111, 17, 29, NULL, 'Lunes', '11:30:00', '13:30:00', NULL, 1, 1, 160, 160),
(257, 122, 17, 29, NULL, 'Miércoles', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(258, 109, 17, 29, NULL, 'Miércoles', '09:00:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(259, 112, 17, 29, NULL, 'Miércoles', '10:50:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(264, 121, 17, 29, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(265, 100, 17, 29, NULL, 'Jueves', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(266, 112, 17, 29, NULL, 'Jueves', '10:50:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(267, 126, 17, 29, NULL, 'Jueves', '12:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(268, 49, 16, 28, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 69, 69),
(269, 48, 17, 29, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 69, 69),
(270, 50, 13, 25, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 69, 69),
(271, 52, 14, 26, NULL, 'Martes', '07:00:00', '07:40:00', NULL, 1, 1, 69, 69),
(272, 119, 17, 29, NULL, 'Miércoles', '14:00:00', '16:00:00', NULL, 1, 0, NULL, NULL),
(273, 106, 14, 26, NULL, 'Viernes', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(274, 186, 11, 23, NULL, 'Lunes', '07:00:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(275, 187, 11, 23, NULL, 'Lunes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(276, 187, 11, 23, NULL, 'Lunes', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(277, 188, 11, 23, NULL, 'Lunes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(278, 189, 11, 23, NULL, 'Lunes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(283, 134, 14, 26, NULL, 'Miércoles', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(284, 134, 14, 26, NULL, 'Miércoles', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(285, 123, 14, 26, NULL, 'Viernes', '12:50:00', '13:30:00', NULL, 1, 1, 166, 166),
(286, 191, 11, 23, NULL, 'Martes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(287, 190, 11, 23, NULL, 'Martes', '07:40:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(288, 193, 11, 23, NULL, 'Martes', '10:10:00', '10:50:00', NULL, 1, 0, NULL, NULL),
(289, 194, 11, 23, NULL, 'Martes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(290, 190, 11, 23, NULL, 'Martes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(291, 199, 11, 23, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(292, 200, 11, 23, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(293, 190, 11, 23, NULL, 'Miércoles', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(294, 202, 11, 23, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(295, 190, 11, 23, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(296, 140, 16, 28, NULL, 'Viernes', '08:20:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(297, 166, 16, 28, NULL, 'Viernes', '10:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(298, 154, 13, 25, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 1, 391, 298),
(299, 146, 17, 29, NULL, 'Jueves', '10:20:00', '10:50:00', NULL, 1, 1, 75, 75),
(300, 145, 14, 26, NULL, 'Jueves', '10:20:00', '10:50:00', NULL, 1, 1, 75, 75),
(301, 144, 16, 28, NULL, 'Jueves', '10:20:00', '10:50:00', NULL, 1, 1, 75, 75),
(302, 143, 13, 25, NULL, 'Jueves', '10:20:00', '10:50:00', NULL, 1, 1, 75, 75),
(303, 194, 11, 23, NULL, 'Miércoles', '10:20:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(304, 200, 11, 23, NULL, 'Miércoles', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(305, 206, 11, 23, NULL, 'Jueves', '10:20:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(306, 191, 11, 23, NULL, 'Jueves', '12:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(307, 167, 13, 25, NULL, 'Viernes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(308, 207, 11, 23, NULL, 'Jueves', '11:30:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(309, 152, 13, 25, NULL, 'Viernes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(310, 200, 11, 23, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(311, 189, 11, 23, NULL, 'Viernes', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(312, 208, 11, 23, NULL, 'Viernes', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(313, 140, 16, 28, NULL, 'Lunes', '09:40:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(314, 209, 11, 23, NULL, 'Viernes', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(315, 210, 11, 23, NULL, 'Viernes', '12:10:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(316, 209, 11, 23, NULL, 'Viernes', '12:50:00', '13:30:00', NULL, 1, 1, 414, 316),
(317, 214, 10, 22, NULL, 'Lunes', '07:00:00', '07:40:00', NULL, 1, 0, NULL, NULL),
(318, 147, 16, 28, NULL, 'Martes', '09:00:00', '10:20:00', NULL, 1, 0, NULL, NULL),
(319, 150, 16, 28, NULL, 'Martes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(320, 215, 10, 22, NULL, 'Lunes', '07:40:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(321, 216, 10, 22, NULL, 'Lunes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(322, 216, 10, 22, NULL, 'Lunes', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(323, 217, 10, 22, NULL, 'Lunes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(324, 218, 10, 22, NULL, 'Martes', '07:40:00', '09:00:00', NULL, 1, 0, NULL, NULL),
(325, 219, 10, 22, NULL, 'Martes', '09:00:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(326, 220, 10, 22, NULL, 'Martes', '10:10:00', '10:50:00', NULL, 1, 0, NULL, NULL),
(327, 221, 10, 22, NULL, 'Martes', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(328, 178, 12, 24, NULL, 'Miércoles', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(329, 222, 10, 22, NULL, 'Martes', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(330, 223, 10, 22, NULL, 'Miércoles', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(331, 184, 12, 24, NULL, 'Viernes', '11:30:00', '12:50:00', NULL, 1, 0, NULL, NULL),
(332, 224, 10, 22, NULL, 'Miércoles', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(333, 215, 10, 22, NULL, 'Miércoles', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(334, 214, 10, 22, NULL, 'Miércoles', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(335, 218, 10, 22, NULL, 'Miércoles', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(336, 225, 10, 22, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(337, 226, 10, 22, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(338, 221, 10, 22, NULL, 'Jueves', '10:10:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(339, 214, 10, 22, NULL, 'Jueves', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(340, 227, 10, 22, NULL, 'Viernes', '07:40:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(341, 228, 10, 22, NULL, 'Viernes', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(342, 229, 10, 22, NULL, 'Viernes', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(343, 146, 17, 29, NULL, 'Viernes', '12:50:00', '13:30:00', NULL, 1, 1, 87, 87),
(358, 143, 13, 25, NULL, 'Miércoles', '10:20:00', '10:50:00', NULL, 1, 1, 89, 89),
(359, 144, 16, 28, NULL, 'Miércoles', '10:20:00', '10:50:00', NULL, 1, 1, 89, 89),
(360, 145, 14, 26, NULL, 'Miércoles', '10:20:00', '10:50:00', NULL, 1, 1, 89, 89),
(361, 146, 17, 29, NULL, 'Miércoles', '10:20:00', '10:50:00', NULL, 1, 1, 89, 89),
(362, 143, 13, 25, NULL, 'Lunes', '10:20:00', '10:50:00', NULL, 1, 1, 43, 43),
(363, 144, 16, 28, NULL, 'Lunes', '10:20:00', '10:50:00', NULL, 1, 1, 43, 43),
(364, 145, 14, 26, NULL, 'Lunes', '10:20:00', '10:50:00', NULL, 1, 1, 43, 43),
(365, 146, 17, 29, NULL, 'Lunes', '10:20:00', '10:50:00', NULL, 1, 1, 43, 43),
(366, 143, 13, 25, NULL, 'Martes', '10:20:00', '10:50:00', NULL, 1, 1, 62, 62),
(367, 144, 16, 28, NULL, 'Martes', '10:20:00', '10:50:00', NULL, 1, 1, 62, 62),
(368, 145, 14, 26, NULL, 'Martes', '10:20:00', '10:50:00', NULL, 1, 1, 62, 62),
(369, 146, 17, 29, NULL, 'Martes', '10:20:00', '10:50:00', NULL, 1, 1, 62, 62),
(370, 143, 13, 25, NULL, 'Viernes', '10:20:00', '10:50:00', NULL, 1, 1, 188, 188),
(371, 144, 16, 28, NULL, 'Viernes', '10:20:00', '10:50:00', NULL, 1, 1, 188, 188),
(372, 145, 14, 26, NULL, 'Viernes', '10:20:00', '10:50:00', NULL, 1, 1, 188, 188),
(373, 146, 17, 29, NULL, 'Viernes', '10:20:00', '10:50:00', NULL, 1, 1, 188, 188),
(378, 157, 13, 25, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(379, 159, 13, 25, NULL, 'Jueves', '10:50:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(380, 165, 13, 25, NULL, 'Viernes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(381, 155, 16, 28, NULL, 'Miércoles', '10:50:00', '11:30:00', NULL, 0, 0, NULL, NULL),
(386, 155, 16, 28, NULL, 'Miércoles', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(387, 160, 16, 28, NULL, 'Jueves', '12:50:00', '14:10:00', NULL, 1, 0, NULL, NULL),
(388, 162, 16, 28, NULL, 'Jueves', '14:10:00', '15:30:00', NULL, 1, 0, NULL, NULL),
(389, 160, 16, 28, NULL, 'Jueves', '10:50:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(390, 144, 16, 28, NULL, 'Jueves', '12:10:00', '12:50:00', NULL, 1, 1, 79, 79),
(391, 155, 16, 28, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 1, 298, 298),
(392, 158, 16, 28, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(393, 177, 12, 24, NULL, 'Miércoles', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(394, 181, 12, 24, NULL, 'Viernes', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(395, 183, 12, 24, NULL, 'Viernes', '10:50:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(396, 174, 12, 24, NULL, 'Jueves', '07:00:00', '08:20:00', NULL, 1, 0, NULL, NULL),
(397, 180, 12, 24, NULL, 'Jueves', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(398, 174, 12, 24, NULL, 'Jueves', '12:10:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(399, 179, 12, 24, NULL, 'Jueves', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(400, 178, 12, 24, NULL, 'Jueves', '11:30:00', '12:10:00', NULL, 1, 0, NULL, NULL),
(401, 223, 10, 22, NULL, 'Viernes', '11:30:00', '13:30:00', NULL, 1, 0, NULL, NULL),
(402, 230, 12, 24, NULL, 'Viernes', '08:20:00', '09:40:00', NULL, 1, 0, NULL, NULL),
(403, 230, 12, 24, NULL, 'Viernes', '10:10:00', '10:50:00', NULL, 1, 0, NULL, NULL),
(404, 230, 12, 24, NULL, 'Miércoles', '10:10:00', '11:30:00', NULL, 1, 0, NULL, NULL),
(405, 220, 10, 22, NULL, 'Viernes', '07:00:00', '07:40:00', NULL, 1, 0, NULL, NULL),
(414, 185, 12, 24, NULL, 'Viernes', '12:50:00', '13:30:00', NULL, 1, 1, 316, 316);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `huellas_templates`
--

CREATE TABLE `huellas_templates` (
  `id_huella` int(11) NOT NULL,
  `user_id_global` varchar(50) NOT NULL,
  `id_estudiante` int(11) DEFAULT NULL,
  `id_profesor` int(11) DEFAULT NULL,
  `fingerprint_data` longtext NOT NULL,
  `formato` enum('HEX','BASE64') DEFAULT 'HEX',
  `slot_index` int(11) DEFAULT NULL,
  `pendiente_sync` tinyint(1) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `huellas_templates`
--

INSERT INTO `huellas_templates` (`id_huella`, `user_id_global`, `id_estudiante`, `id_profesor`, `fingerprint_data`, `formato`, `slot_index`, `pendiente_sync`, `activo`, `fecha_creacion`) VALUES
(49, 'EST_25', 25, NULL, '03035f2320013701970000000000000000000000000000000000000000000000000000000000000000000000000000000c0000008d0000000000000cffffffffffffffffeeaaaaaeaaaaa6999599a9995555555554444401040101010101010100000000000000000000000000000000000000000000010101010101010101015f8727de7419e95e4a1a6c5e2ba11a1e57a7ec7e64ababfe8831293e66b1965e5eb3ec7e6abe805e7887667f848850bf7810e79f6a91687f7a2ceadf4ec2809f22901a3c6d9e6abc6da1951c2633967c1d33ecbc1d8844da220a1b5a3492d9ba2193439a1e2c1ada1c2fc15b29959a38232fd6b8318f42598493aa998693d4f92b93c2571294031e42c6975e0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000030361240001200198000000000000000000000000000000000000000000000000000000000000000000000000000000060000008500000000000000cffffffffffffffffeeaaaaaaaaa9a659599aa55545555555444440000000000000000000000000000000000000000000000000000000000000000000000000000000000750ea83e108f039e4617acde6f98e9fe269cd99e51256cfe5da9ac9e10aa575e5eaf96de5531803e603c815e46bdc1de37c397de5c84a7ff24059a1f7686a73f670fe91f722c2aff6fc3425f1b08449c1e8f03fc6820157c228b5a5d318f1a3a691d2afa0c2fc8ba1f32d6fa2f8bc29b26919a980cadc35818aec0f81caf177823375738233b971822394119288fc2d700000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 1, 1, '2026-08-10 18:24:33'),
(50, 'EST_25', 25, NULL, '03036027100141019b0000000000000000000000000000000000000000000000000000000000000000000000000000000400000091000000000000000cfccffffffffffffffffeeaaaaaaaaaaa9995966956595545555555544401010101010101010101010101010101010101010101010101010101010101010101010101017318e7de42a1ec9e2327599e4e2facbe59b3ac7e5bb956be7dbaab3e53bbacfe5e46811e620ce69f590f283f20901adf7410e69f7f92513f649a293f6b242a5f6fb5eb9f41c9813f179284bc7e9c677c7f1f53bc64aa55dc2d999a1a65a72b3a14b59bda173d17da1dc1975a214280da1f159a7b2b96427b1a9843fb231c1b58123900d817395758249a42b52c89dbde0d28da7e0bafc45e5d88d19c000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000303601e000120019b0000000000000000000000000000000000000000000000000000000000000000000000000000000400000085000000000000000cffffffffffffffffeeaaaaaaaaaaa9596666655515555555544400000000000000000000000000000000000000000000000000000000000000000000000000000000002b099bfe730a907e7017e7be41212cbe6aa229fe21a5d9de0ca8da9e4c2f6cbe0bafc47e5833ac5e12b4999e1738971e5a3956be513bacfe1f8ddb1f570e27bf160f043f718f667f621928ff64ab15df6eb5ab5f5c0811bc64266afc221b9b7d2d17da5a269842da600b667b2a9482fb1b949b981996051900000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-10 18:25:21'),
(51, 'EST_25', 25, NULL, '03034a33600189017f0000000000000000000000000000000000000000000000000000000000000000000000000000000c00090095000c000300030330cc0f0cfffffffeeabbfbeeeaaaeaaa6599a666a666666554545445151114010101010101010101010101010101010101010101010101010101010101010101010101011e2b271e17b6633e32a06afe2e2a0fde2eaea89e411d2cfe2b42869e40b391de49344fbe5ca1171e6da8d93e7fbce45e7751a97e95af1d5e8d3ed15e92b9e23e86ce12de4429933f532bd4df4f46d95f6831177f76a7597f28b40c5c3fa36cdc45a3565c671d591c6555eb1c67d215bc76cc143c444402fd5dbd2a3d6fcb153d2eb4e65a68a0d8ba7f315b5a64a4431b563ceb3b7036181b783a603b812e86fb8b3762db313d23d83c3ec6988e348e78343866b9333a8ad9383fe079494002594e3eeb5972b91539671a59560000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035332000120017f0000000000000000000000000000000000000000000000000000000000000000000000000000000a000000850000000c000033fffffffffffffffeeaaaaaaaaaaa669565a655540555155144544400000000000000000000000000000000000000000000000000000000000000000000000000000000004584035e2e04ec7e608a873e4c8b591e448d44de6b90493e1d120fde40979b1e421bd8fe6c9ea05e63a9627e43b8409e19bb05be653e269e44bf5a9e4fc06b7e25c11c1e5495dc7f559d9e1f2124a29f52a960bf69ad22bf380c159c5f11891c61149f9c7722625c0c33c75c13b3defc2438dd7c229e8c3d3828467d23b55e7d380f029a712461fa59b4649a69a38c5b3624877b4baee15b4633e97b5f34503b523bec1b299340182a190fd8583c57b8339302f92f135419269769b92c1f8b993021879946b0de790000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-13 18:02:08'),
(52, 'EST_25', 25, NULL, '03035717000120018d00000000000000000000000000000000000000000000000000000000000000000000000000000010000100790000300cc33ccfffffffffffffbaaaaaaaaaa999595555951515545511004400000000000000000000000000000000000000000000000000000000000000000000000000000000000000004f06201e470c1f1e398eddfe4214067e7116e77e26181d3e3f19dc5e6199d87e292c5b9e372d5b7e5130587e3fb281fe32b3dabe27351b7e7235137e733b125e52149d7f5c2018ff4c1ac35d47979d1a4f36571a48b8ac3a4d3b16fb000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035f2c00012001800000000000000000000000000000000000000000000000000000000000000000000000000000000a00000085000000000330cccfffffffffffffeeeeaaaaaaaaaaa599665955555515554451001400000000000000000000000000000000000000000000000000000000000000000000000000000000005385d1de340ba7be0c90243e761050be3191ce7e0d16a39e1921a33e4f24a69e56aa0fbe5332507e37b5271e27b818be223fd91e6208933f49964eff2a1c249f19325e5f1238c3ff331765dc519e26dc3a1ea55c0bb0069c0bb347fc3a18ce5d5aa1cfda429fa55b48a229bb38a3a69b2b24a49b08aa893b3babe5fb342ed69b3caf11db6812d0786494e6f8579f50382ea1cf380c26c8780f28a0d85f94d039312463f92c29e2b72caae3972b2c27f70000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-13 18:04:12'),
(53, 'EST_25', 25, NULL, '03035c2018016901870000000000000000000000000000000000000000000000000000000000000000000000000000000e0000008f000000000cccffffcffffffcfffffbfbeeeaaaaaeaa996665959555655555555515144004001010101010101000000000000000000000000000001010101010101010101010101010101006e14a51e4c96689e3598c45e48a19a3e7a2065be2d24db3e52a82a9e742fe93e70b469be3ab6815e703b551e593cac7e2ec0823e5849ec7e474b417e5c9a65bf6e1d90ff684415df464f81bf3430c1bb5da613d8353359b85ea467d92886865e610c215e268f5c3e2194c65e4d8c5df7578f1f14558c8d714d8fdf31540d23af00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000303611700012001890000000000000000000000000000000000000000000000000000000000000000000000000000000a0000007d000000000c0333cffffffffffffbeeeeeaaaaaaaa65995959555555155454444000000000000000000000000000000000000000000000000000000000000000000000000000000000000006a0625de4886e95e3108451e4311dafe7492667e27141bfe4c18ab5e6ca1a9fe6825ea7e3226421e66acd5de4fad403e24afc2fe4cba803e3bbb423e580b667f690f11bf5db5569f3a3f827f2ca0427b571714982d22da78589568990000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-17 12:26:32'),
(54, 'EST_25', 25, NULL, '03035d2424013f0186000000000000000000000000000000000000000000000000000000000000000000000000000000090000008f0000000030033ccf33fcfffffffffefeeeeaaabaaaaaa996595956599955555455111404400101010000000000000000000000000001010101010101010101010101010101010101010101420b2a7e2c0e445e3f15da5e6f94a73e241a5bbe7c99677e6aa3e9fe162704fe62a82b9e4fb0401e2735825e3ebf421e530ea65f6411d19f479b6b5f79a313ff68b0565f5f37965f4bbe803f3d43827f7d3ba91c773c143c77436adc78c6ac3c332a415d2c2581bb541a14d82e285998549868b94384a71e0c0e067e0c23047c0c20063a0997885b0c1d06180c1a45d60000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035f1d000120018b0000000000000000000000000000000000000000000000000000000000000000000000000000000400000083000000000000333cf3fcfffffffeffbfbbbaaaaaaaaaa9965955595555554555504000000000000000000000000000000000000000000000000000000000000000000000000000000000003704ea7e2087845e328f9a5e638fa73e17935bbe6f94a77e5c9ea9fe081f84fe5422ab9e40aa401e17ae825e2e39021e4788e65f580c919f3a956b5f6b9e53ff59ab165f4f32165f3b38803f2c3d027f6cb6e91c66b7143c663e6adc6741ec3c24a3815d1e1ec1bb471454d8202199984812a8b90000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-17 14:09:12'),
(55, 'EST_6a83194d6f4be_213', 28, NULL, '03035e331c0146017d000000000000000000000000000000000000000000000000000000000000000000000000000000050000009300000000000003333fcf3f3ffffffffffeeeeaeeaaaaaaa6666995659656665555554554444001010101010101010101010101010101010101010101010101010101010101010101010101230b201e5316a47e5d1ee59e31adda1e1b3842de403bd7fe5b41a8be5f08a31f660c23bf4c92e37f1f9cdcff6728d21f47a969df2431437f5fbd937f1ebd831f3818dc3d48a4969d38c1821d3dc3593d2f8ae21a5ca5675a430ce17b0f14c65b3da1197b3d9628583f22eb985a2352780b16c9390a1dc8190a2047f90a2348792d351a59303580f9358692f60b1ba1560c260576330748173b0c5ed70a99485709a78617358888553f92e4753a8e60b33d0f9b133b9161333f91de737c14e69e59c853be53cbe95e7243521f0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035c2f00012001750000000000000000000000000000000000000000000000000000000000000000000000000000000900000085000000000003333ffffffffffffffebbaaaaaaaaaa665965959995555551551110040000000000000000000000000000000000000000000000000000000000000000000000000000000000218b1fde5216643e5c1e655e312dd9de1b38429e403b97be5bc1287e5d87e2df648ba37f4b12a33f1e9cdcbf66a851df4729299f2431433f5fbd133f1ebd82df37189bfd4824565d394141dd3e4318fd2e0ae1da5c24e71a418ca13b0e15061b3ca0d93b3c95e8183ea2ab5859a2d238099708f9089e07d908a087b9092388392d351a19303580b9340652b6099be1160b264536318707d7398c1e970919881708a7c5d7340848153e12a435390e20733b8f5ad33a1120f33e119e330000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 1, 1, '2026-08-17 14:23:37'),
(56, 'EST_6a83194d6f4be_213', 28, NULL, '030358281c01390175000000000000000000000000000000000000000000000000000000000000000000000000000000170000008b00003333fffcffff3ffffffeeeaaabaaaaba999965966956655555555111100010000040010101010101010101010101010101010101010101010101010101010101010101010101010101290d9f5e65a0e4fe3a2f997e253b021e4a3d577e65c3a83e658a22bf6c8e633f5414e2bf159885df5918a43f261f5c7f6fab123f2fb3c6df693f531f7ac4121f52a9e9dc14af841c498f213d3a08df7a0f9c059a13ac843a4398ea1b411adafb4622d8db5026969b4343819b47c518bb4824ab183c0b8e59380d2399102987393a37811943925fd744135cf764a6a49736b71a170e9f47350fa706f56425d2f50000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000303572800012001750000000000000000000000000000000000000000000000000000000000000000000000000000000600000085000000000000033cffffffffffffffeeeeaaaaaaaaa9999565959a5555554555044400000000000000000000000000000000000000000000000000000000000000000000000000000000002789601e601fa5be31ac1a3e1a3642de3eba983e58c228fe6488e37f6a8da3ff5112e37f1213069f5516e4ff211add3f67aa52ff262f879f5d3e13df6dc3d2df4b27aa9c0c2a04dc478ca1fd3985603a0b96465a0c2704fa3f95eadb3d17dbbb401fd99b49a4175b3640425b3ac2197b41a1ebd83b084f193689a4590923c7f93033c1d9410f609741905db75da565572cb35ad7091987f5092147b55da493b5000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-17 14:25:14'),
(57, 'EST_6a831b915dfa6_218', 29, NULL, '03035d3520018d017b00000000000000000000000000000000000000000000000000000000000000000000000000000016000000a30000cccf3cfcff33fffffffffffffffeabaaaaaaaaaaa59a5659555559555655555544111040010400400101010101010101010101010101010101010101010101010101010101010101012fa49fbe1ab3c5fe1c3a44de3cbad9fe16bec53e7e4565de50c7539e444b559e814ba6de68d3913e1d54187e3154d7de1ed8007e475ae95e5b3050bf77b9cf1f2c3b9bdf46bed67f323f811f2fc659df16c8853f765651ff6857a75f31b19e1c4a3118dd2ab29dbd49b4175d1aacc77a603a50fa8026e53b8428cf1b64b9661b49a324184625221917af48394a26a55747a79e976008d0be570f67de131e089e11a5097e369b623f1295603c0f984a1c2c9b61dc211f9f3c6119cd7a6c8fd0bb6f128dfb66194cdb1e1be0b92499e196229b08d5000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035b2b0001200175000000000000000000000000000000000000000000000000000000000000000000000000000000120000008500000033cf3cffffffffffffefbaaaaaaaaaaaaa996555555555551554044040000400000000000000000000000000000000000000000000000000000000000000000000000000000000005f8711be550ce8de0e17899e261e609e0b1e8a7e7621247e27ac9e9e4facd17e0838c57e6db8cfbe373b185e7143661e3217233f4f1b0e7f0ca2c93f1fad1e5f33b61a1f1f365cbf253b025f1fc1daff0f8ea13c0c118b1c2816a2dc1b9a203c3ea5dd9c0aa8881c412d595d3eb0985d0c3286bd5c97ce7a0a2c07da54b7d13a6a8ed1bb6c118efb6197cddb412224db082fc85b5a37261b409da458199621b93d1f6279209462961e1549d5000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-17 14:33:14'),
(58, 'PROF_6a872970109a6_428', NULL, 21, '03035f1d2c01610186000000000000000000000000000000000000000000000000000000000000000000000000000000080000008f0000000000c033cf3ff3fff3fffffeefeeeeeeeeaaaa6a999999956555555655545554444001010100000000000000000000000000000000010101010101010101010101010101010101014d0454de1c0a063e480c283e308d851e7396661e4296da9e26995c5e1419de3e6ca5e95e692a29be332b81fe6730d55e4f31ecde26b542be5d3ad6be4fbf2cfe3e40023e690be47f5710663f6893915f4a1ceb3f2ca5827b569c15382d285a78581a689939ce191e7243ec5f753d53dc7bbd693c000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035f1a0001200187000000000000000000000000000000000000000000000000000000000000000000000000000000050000007f000000000000ccfcfffcffffbfeefeeeeebbaaaa9aa6666599555555545554444000000000000000000000000000000000000000000000000000000000000000000000000000000000000032081a5e158b5bfe3b0eebfe3ba2807e52a316de1225425e38af007e27afc29e23bed9be588691df630ba7bf6998549f49aa971f2634027f5d362cff551beb5c612fd47c67afe9dc59196add201b01bd1a96023b468dd5381b185918099c42d8091e1c58460c69b90000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-08-31 15:09:47'),
(61, 'PROF_6a872970109a6_428', NULL, 21, '0303592d3001490194000000000000000000000000000000000000000000000000000000000000000000000000000000120000009500000000f30033ffffffffffffffeeaaabaabaaaeaaaa666655595595595114551104101104401010101010101010101010101000000000000000000000000010101010101010101010101378f59de3f1b97de2c1ec17e6d9c127e7d1aa79e63a691be182e837e173281de7ca9a89e6cb0d23e4634d5de2d3996be5abb935e38c0557e1ec3567e30c854be70c5ea1e2711827f42a1571f633568bf6bba52ff2247151f61472bff5e0bd27c5b0f283c4714d65c58ac14fc4611167d2d15c2bd463b6bda4b3ad55a57c4295a5345133a17a8851b5d2928fb3417da383817c078331a4178129dc4d8192683348a20d1fe8e40931e8317113c851b27fc4807159d0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003035e28000120019200000000000000000000000000000000000000000000000000000000000000000000000000000014000000850000000ff3fffffffffffefffaaabaaaaaaaaaa59995555555555111451100400004000000000000000000000000000000000000000000000000000000000000000000000000000000000036091b1e3915d91e251742be669a53be769a68de5923d2fe0ba504be0929031e70a929de5eaed37e372f571e1d31d7fe4937d49e263996be0b3a17be1b4095fe5b43eb5e250a03bf3a9bd85f53b269ff5a38143f0dbe565f4bc3803f5d0913bc590c297c430fd79c4ca8563c438c17bd298e83fd3535c01a3a35d69a433fea9a3e40947a0d1f465b5225ea3b2f915b78339181b82d9382b80c1406180f9d4474000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-09-07 16:17:00'),
(62, 'EST_6a9596c7e0255_512', 85, NULL, '0303621c1801350183000000000000000000000000000000000000000000000000000000000000000000000000000000030000008f0000000000000000cccffffffffffffffffeeaaaaaaaaaaaaa99999555955555555544554001010101010101010101010101010101010101010101010101010101010101010101010101016f8ee51e5e14683e4499017e309e827e71a229be7fa6943e6830ac7e7a312b1e6942975e5b4340fe7943413e24c7d9fe698bd0bf2f0cc5ff7a8da53f5891683f2f98841f3c98db5f51a0173f57a66bff5f2aacdf4a2ec11f3f41021f84416bbf4f43989f5a9c2a98599ad67911975c9f0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000303611f000120017e000000000000000000000000000000000000000000000000000000000000000000000000000000030000008500000000000000033ffffffffffffffffbaaaaaaaaaaaaaa65556555555555545544000000000000000000000000000000000000000000000000000000000000000000000000000000000038969b9e4298013e7020aa9e5f2a6cde672f801e76c341be160685ff4c891c9f2b0c859f6e0f24ff0e975c9f2b97837f2b9d82bf4f9e971f55a52bff47ad815f5e33169f54b46cbf3bc1027f55c2415f5c93a81c5498d69c43059d3d5790e7bd6608e2ba3807dd1b581beabb542b991b3d069d79678b90d94fa9c1f9000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', 'HEX', NULL, 0, 1, '2026-09-07 17:01:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instrumentos_evaluativos`
--

CREATE TABLE `instrumentos_evaluativos` (
  `id_instrumento` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `instrumentos_evaluativos`
--

INSERT INTO `instrumentos_evaluativos` (`id_instrumento`, `nombre`, `activo`, `created_at`) VALUES
(1, 'Lista de cotejo', 1, '2026-08-31 13:26:23'),
(2, 'RSA', 1, '2026-08-31 13:26:23'),
(3, 'Rubrica', 1, '2026-08-31 13:26:23'),
(4, 'Prueba practica', 1, '2026-08-31 13:26:23'),
(5, 'Cuestionario', 1, '2026-08-31 13:26:23'),
(6, 'Portafolio', 1, '2026-08-31 13:26:23'),
(7, 'Guia de ejercicios', 1, '2026-08-31 13:26:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_materia`, `nombre`, `activo`) VALUES
(1, 'Matemática Actualizada', 0),
(2, 'Historia', 0),
(3, 'Matematica Actualizada', 0),
(4, 'Informatica Actualizada', 0),
(8, 'MATEMATICA', 1),
(10, 'ADMINISTRACION FINANCIERA', 1),
(11, 'LITERATURA', 1),
(12, 'GUARANI', 1),
(13, 'INGLES', 1),
(14, 'PSICOLOGIA', 1),
(15, 'COREANO', 1),
(16, 'ALGORITMICA', 1),
(17, 'CIENCIAS', 1),
(18, 'ECONOMIA', 1),
(19, 'PROYECTO', 1),
(20, 'BIOLOGIA', 1),
(21, 'CASTELLANO', 1),
(22, 'EDUCACION FISICA', 1),
(23, 'QUIMICA', 1),
(25, 'FISICA', 1),
(26, 'INFORMATICA', 1),
(27, 'GABINETE LABORATORIO', 1),
(28, 'HISTORIA Y GEOGRAFIA', 1),
(29, 'METODOLOGIA', 1),
(30, 'ROBOTICA', 1),
(31, 'MATEMATICA APLICADA', 1),
(32, 'FILOSOFIA', 1),
(33, 'EDUCACION CRISTIANA', 1),
(34, 'MUSICA', 1),
(35, 'ORIENTACION', 1),
(36, 'CIENCIAS NATURALES Y SALUD', 1),
(38, 'ESTADISTICAS', 1),
(39, 'EDUCACION CRISTIANA', 0),
(40, 'LABORATORIO PROGRAMACION', 1),
(41, 'LABORATORIO HARDWARE', 1),
(42, 'CIENCIAS SOCIALES', 1),
(43, 'LABORATORIO SOFTWARE', 1),
(44, 'ANTROPOLOGIA', 1),
(45, 'GEOLOGIA', 1),
(46, 'EDUCACION AMBIENTAL', 1),
(47, 'LOGICA MATEMATICA', 1),
(48, 'GABINETE LABORATORIO', 0),
(49, 'DIBUJO TECNICO', 1),
(50, 'DESARROLLO PERSONAL', 1),
(51, 'ARTES PLASTICAS', 1),
(53, 'RECESO', 1),
(57, 'CULTO', 1),
(58, 'BIOLOGÍA', 0),
(59, 'GUARANÍ', 0),
(68, 'ETICA', 1),
(69, 'ESTADISTICA', 1),
(70, 'EDUCACION VIAL', 1),
(71, 'TRABAJO Y TECNOLOGIA', 1),
(72, 'DESARROLLO PERSONAL', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nodos_esp32`
--

CREATE TABLE `nodos_esp32` (
  `id_nodo` int(11) NOT NULL,
  `node_id` varchar(100) NOT NULL,
  `lora_id` int(11) DEFAULT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `tipo` enum('CAPTURA','GATEWAY','AULA') NOT NULL,
  `estado` enum('online','offline') DEFAULT 'offline',
  `ultimo_heartbeat` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `nodos_esp32`
--

INSERT INTO `nodos_esp32` (`id_nodo`, `node_id`, `lora_id`, `room_id`, `tipo`, `estado`, `ultimo_heartbeat`, `activo`, `fecha_creacion`) VALUES
(1, 'ESP32_AULA_A', 101, '3_RO_BTI', 'AULA', 'online', NULL, 1, '2026-05-18 14:52:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_anuales`
--

CREATE TABLE `planes_anuales` (
  `id_plan` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `competencia_general` text DEFAULT NULL,
  `competencia_especifica` text DEFAULT NULL,
  `institucion_fuente` varchar(255) DEFAULT NULL,
  `materia_fuente` varchar(255) DEFAULT NULL,
  `profesor_fuente` varchar(255) DEFAULT NULL,
  `curso_fuente` varchar(100) DEFAULT NULL,
  `turno_fuente` varchar(100) DEFAULT NULL,
  `anio_fuente` int(11) DEFAULT NULL,
  `dias_clase_fuente` varchar(255) DEFAULT NULL,
  `importacion_origen_json` longtext DEFAULT NULL,
  `estado` enum('BORRADOR','PUBLICADO','ARCHIVADO') NOT NULL DEFAULT 'BORRADOR',
  `observaciones` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `planes_anuales`
--

INSERT INTO `planes_anuales` (`id_plan`, `id_asignacion`, `anio`, `competencia_general`, `competencia_especifica`, `institucion_fuente`, `materia_fuente`, `profesor_fuente`, `curso_fuente`, `turno_fuente`, `anio_fuente`, `dias_clase_fuente`, `importacion_origen_json`, `estado`, `observaciones`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 131, 2026, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BORRADOR', NULL, 29, '2026-08-31 14:09:49', '2026-08-31 14:09:49'),
(7, 33, 2026, 'Aplica conceptos y procedimientos básicos en la elaboración de: presupuesto, valuación de inventarios e índices financieros en situaciones problemáticas con datos reales acordes a diferentes contextos.', 'Comprende las implicancias de la correcta planificación, organización y métodos aplicado en las empresas adecuadas al sistema jurídico nacional.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PUBLICADO', 'Estas son las observaciones generales del plan de administración financiera del tercer curso del bachiller técnico en informática', 29, '2026-08-31 14:11:03', '2026-08-31 14:40:28'),
(8, 23, 2026, 'Desarrolle sistemas informáticos básicos a partir del desarrollo de proyectos y construcción de procesos de información de acuerdo a las necesidades del usuario.', 'Gestione una base de datos mediante un lenguaje de programación imperativo y declarativo del tipo SQL o similar considerando medidas de seguridad e integridad de los datos.\nConoce las herramientas de desarrollo en plataforma web de aplicaciones en CSS y PHP.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PUBLICADO', 'Observaciones generales del plan anual de tercer curso bachiller técnico en informática', 28, '2026-08-31 14:46:24', '2026-08-31 14:55:15'),
(16, 121, 2026, '- Aplica procedimientos administrativos, basados en el derecho impositivo utilizando herramientas tecnológicas que permitan la obtención de informes contables, con miras a la toma de decisiones acertadas.', '- Establece relaciones contables entre los diferentes tipos de documentos y su implicancia en los movimientos y estados financieros de una empresa, mediante la elaboración del plan de cuentas y manejo de los libros. - Maneja los Libros Diario, Inventario, Mayor, Compras y Ventas, mediante el establecimiento de relaciones contables que generen diferentes tipos de documentos y su implicancia en los estados financieros de una empresa.', 'COL. PRIV. PRESB. SAN PABLO', 'ADMINISTRACIÓN FINANCIERA', 'LIC. DANILO FRANCO', '2ºC-BTI', 'MAÑANA', 2026, NULL, '{\"schema_version\":\"1.0\",\"source\":{\"filename\":\"Plan Anual - segundo curso - BTI - Administracion financiera.pdf\",\"format\":\"ADMINISTRACION_FINANCIERA\",\"sha256\":\"8ec14c4156401c3f9b58ca011d9720d31cce3ac0ad0f05c54c19fbdb3814b333\",\"pages\":1,\"text_layer\":true,\"extractor\":{\"name\":\"pdftotext\",\"mode\":\"table\",\"version\":\"4.00\",\"elapsed_ms\":32.62}},\"warnings\":[],\"confidence\":{\"scale\":\"rule_completeness_0_100\",\"metadata\":100,\"estructura\":100,\"asociaciones\":100,\"global\":100,\"criteria\":{\"metadata\":{\"expected\":[\"institucion\",\"materia\",\"profesor\",\"curso\",\"turno\",\"anio\",\"competencia_general\",\"competencia_especifica\"],\"found\":[\"institucion\",\"materia\",\"profesor\",\"curso\",\"turno\",\"anio\",\"competencia_general\",\"competencia_especifica\"]},\"estructura\":[{\"criterion\":\"Existe al menos una unidad\",\"weight\":15,\"passed\":true},{\"criterion\":\"Todas las unidades tienen capacidad\",\"weight\":20,\"passed\":true},{\"criterion\":\"Todas las capacidades tienen tema\",\"weight\":20,\"passed\":true},{\"criterion\":\"Todos los temas tienen indicador\",\"weight\":20,\"passed\":true},{\"criterion\":\"Órdenes secuenciales no vacíos\",\"weight\":5,\"passed\":true},{\"criterion\":\"Códigos jerárquicos coherentes o no aplicables\",\"weight\":10,\"passed\":true},{\"criterion\":\"Horas presentes cuando el formato las exige\",\"weight\":10,\"passed\":true}],\"asociaciones\":[{\"criterion\":\"Sin temas huérfanos\",\"weight\":40,\"passed\":true},{\"criterion\":\"Sin indicadores huérfanos\",\"weight\":40,\"passed\":true},{\"criterion\":\"Sin columnas ambiguas\",\"weight\":10,\"passed\":true},{\"criterion\":\"Sin reconstrucción conservadora de tabla\",\"weight\":10,\"passed\":true}],\"formula_global\":\"30% metadata + 40% estructura + 30% asociaciones\"}},\"debug\":{\"columns\":{\"unit\":{\"x_char_start\":0,\"x_char_end\":29,\"x_relative_start\":0,\"x_relative_end\":0.1289},\"capacity\":{\"x_char_start\":29,\"x_char_end\":59,\"x_relative_start\":0.1289,\"x_relative_end\":0.2622},\"topic\":{\"x_char_start\":59,\"x_char_end\":92,\"x_relative_start\":0.2622,\"x_relative_end\":0.4089},\"indicator\":{\"x_char_start\":92,\"x_char_end\":133,\"x_relative_start\":0.4089,\"x_relative_end\":0.5911},\"procedure\":{\"x_char_start\":133,\"x_char_end\":161,\"x_relative_start\":0.5911,\"x_relative_end\":0.7156},\"instrument\":{\"x_char_start\":161,\"x_char_end\":191,\"x_relative_start\":0.7156,\"x_relative_end\":0.8489},\"time\":{\"x_char_start\":191,\"x_char_end\":205,\"x_relative_start\":0.8489,\"x_relative_end\":0.9111},\"process\":{\"x_char_start\":205,\"x_char_end\":225,\"x_relative_start\":0.9111,\"x_relative_end\":1}},\"unit_markers\":[{\"code\":\"I\",\"page\":1,\"line\":23},{\"code\":\"II\",\"page\":1,\"line\":35},{\"code\":\"III\",\"page\":1,\"line\":47},{\"code\":\"IV\",\"page\":1,\"line\":59},{\"code\":\"V\",\"page\":1,\"line\":73},{\"code\":\"VI\",\"page\":1,\"line\":85}],\"association_rule\":\"Celdas verticales por punto medio entre unidades; tema/indicador por orden solo cuando las cantidades coinciden.\",\"format_detection\":{\"format\":\"ADMINISTRACION_FINANCIERA\",\"error_type\":null,\"threshold\":60,\"minimum_margin\":15,\"winning_score\":105,\"margin\":89,\"scores\":{\"ADMINISTRACION_FINANCIERA\":{\"score\":105,\"signals\":[{\"signal\":\"Título Administración Financiera\",\"weight\":25,\"matched\":true},{\"signal\":\"Unidad Temática y Capacidades\",\"weight\":20,\"matched\":true},{\"signal\":\"Procedimientos Evaluativos\",\"weight\":15,\"matched\":true},{\"signal\":\"Instrumentos Evaluativos\",\"weight\":15,\"matched\":true},{\"signal\":\"Tiempo HC/Horas Cátedra\",\"weight\":10,\"matched\":true},{\"signal\":\"Proceso/Meses\",\"weight\":10,\"matched\":true},{\"signal\":\"Competencia General\",\"weight\":5,\"matched\":true},{\"signal\":\"Competencia Específica\",\"weight\":5,\"matched\":true}]},\"EXCEL_AVANZADO\":{\"score\":16,\"signals\":[{\"signal\":\"Título Microsoft Excel\",\"weight\":25,\"matched\":false},{\"signal\":\"Unidad, Capacidades y Temas\",\"weight\":20,\"matched\":false},{\"signal\":\"Columna Check\",\"weight\":18,\"matched\":false},{\"signal\":\"Proceso para desarrollo\",\"weight\":22,\"matched\":false},{\"signal\":\"Procedimientos\",\"weight\":8,\"matched\":true},{\"signal\":\"Instrumentos\",\"weight\":8,\"matched\":true},{\"signal\":\"Códigos de tema\",\"weight\":10,\"matched\":false},{\"signal\":\"Códigos de indicador\",\"weight\":10,\"matched\":false}]},\"COMPETENCIA_CONTENIDO\":{\"score\":0,\"signals\":[{\"signal\":\"Competencia y Capacidad\",\"weight\":20,\"matched\":false},{\"signal\":\"Contenidos\",\"weight\":15,\"matched\":false},{\"signal\":\"Área Transversal\",\"weight\":15,\"matched\":false},{\"signal\":\"Metodología de Enseñanza\",\"weight\":20,\"matched\":false},{\"signal\":\"Medios de Verificación\",\"weight\":20,\"matched\":false},{\"signal\":\"Fecha\",\"weight\":5,\"matched\":false},{\"signal\":\"Días de clase\",\"weight\":5,\"matched\":false}]}}},\"counts\":{\"unidades\":6,\"capacidades\":6,\"temas\":6,\"indicadores\":18}}}', 'ARCHIVADO', NULL, 29, '2026-10-05 14:04:34', '2026-10-05 14:10:37'),
(17, 163, 2026, NULL, NULL, 'COLEGIO “PRESBITERIANO SAN PABLO”', 'Informática', 'LIC. DANILO FRANCO', '1° BCB', NULL, 2026, 'VIERNES', '{\"schema_version\":\"1.0\",\"source\":{\"filename\":\"Plan Anual - segundo curso - BCB - Excel Avanzado.pdf\",\"format\":\"COMPETENCIA_CONTENIDO\",\"sha256\":\"268cb10c7d3ae3ad421ed615b44dedc59fcbe8662f5c5aab75d993a8c0b3cdac\",\"pages\":2,\"text_layer\":true,\"extractor\":{\"name\":\"pdftotext\",\"mode\":\"table\",\"version\":\"4.00\",\"elapsed_ms\":49.82}},\"warnings\":[{\"type\":\"TABLE_STRUCTURE_WARNING\",\"page\":null,\"line\":null,\"text\":null,\"message\":\"Los CONTENIDOS sin numeración se conservaron como un tema compuesto por capacidad; sus asociaciones se verificaron por los límites de las celdas.\"},{\"type\":\"SOURCE_METADATA_MISMATCH\",\"page\":null,\"line\":null,\"text\":\"Plan Anual - segundo curso - BCB - Excel Avanzado.pdf\",\"message\":\"El nombre del archivo sugiere curso 2, pero el PDF declara 1° BCB.\"}],\"confidence\":{\"scale\":\"rule_completeness_0_100\",\"metadata\":85,\"estructura\":100,\"asociaciones\":90,\"global\":93,\"criteria\":{\"metadata\":{\"expected\":[\"institucion\",\"materia\",\"profesor\",\"curso\",\"anio\",\"dias_clase\"],\"found\":[\"institucion\",\"materia\",\"profesor\",\"curso\",\"anio\",\"dias_clase\"]},\"estructura\":[{\"criterion\":\"Existe al menos una unidad\",\"weight\":15,\"passed\":true},{\"criterion\":\"Todas las unidades tienen capacidad\",\"weight\":20,\"passed\":true},{\"criterion\":\"Todas las capacidades tienen tema\",\"weight\":20,\"passed\":true},{\"criterion\":\"Todos los temas tienen indicador\",\"weight\":20,\"passed\":true},{\"criterion\":\"Órdenes secuenciales no vacíos\",\"weight\":5,\"passed\":true},{\"criterion\":\"Códigos jerárquicos coherentes o no aplicables\",\"weight\":10,\"passed\":true},{\"criterion\":\"Horas presentes cuando el formato las exige\",\"weight\":10,\"passed\":true}],\"asociaciones\":[{\"criterion\":\"Sin temas huérfanos\",\"weight\":40,\"passed\":true},{\"criterion\":\"Sin indicadores huérfanos\",\"weight\":40,\"passed\":true},{\"criterion\":\"Sin columnas ambiguas\",\"weight\":10,\"passed\":true},{\"criterion\":\"Sin reconstrucción conservadora de tabla\",\"weight\":10,\"passed\":false}],\"formula_global\":\"30% metadata + 40% estructura + 30% asociaciones\"}},\"debug\":{\"association_rule\":\"Límites de celdas impresos; continuación entre páginas; indicadores por inicio de viñeta.\",\"table_regions\":3,\"format_detection\":{\"format\":\"COMPETENCIA_CONTENIDO\",\"error_type\":null,\"threshold\":60,\"minimum_margin\":15,\"winning_score\":100,\"margin\":90,\"scores\":{\"COMPETENCIA_CONTENIDO\":{\"score\":100,\"signals\":[{\"signal\":\"Competencia y Capacidad\",\"weight\":20,\"matched\":true},{\"signal\":\"Contenidos\",\"weight\":15,\"matched\":true},{\"signal\":\"Área Transversal\",\"weight\":15,\"matched\":true},{\"signal\":\"Metodología de Enseñanza\",\"weight\":20,\"matched\":true},{\"signal\":\"Medios de Verificación\",\"weight\":20,\"matched\":true},{\"signal\":\"Fecha\",\"weight\":5,\"matched\":true},{\"signal\":\"Días de clase\",\"weight\":5,\"matched\":true}]},\"ADMINISTRACION_FINANCIERA\":{\"score\":10,\"signals\":[{\"signal\":\"Título Administración Financiera\",\"weight\":25,\"matched\":false},{\"signal\":\"Unidad Temática y Capacidades\",\"weight\":20,\"matched\":false},{\"signal\":\"Procedimientos Evaluativos\",\"weight\":15,\"matched\":false},{\"signal\":\"Instrumentos Evaluativos\",\"weight\":15,\"matched\":false},{\"signal\":\"Tiempo HC/Horas Cátedra\",\"weight\":10,\"matched\":false},{\"signal\":\"Proceso/Meses\",\"weight\":10,\"matched\":true},{\"signal\":\"Competencia General\",\"weight\":5,\"matched\":false},{\"signal\":\"Competencia Específica\",\"weight\":5,\"matched\":false}]},\"EXCEL_AVANZADO\":{\"score\":0,\"signals\":[{\"signal\":\"Título Microsoft Excel\",\"weight\":25,\"matched\":false},{\"signal\":\"Unidad, Capacidades y Temas\",\"weight\":20,\"matched\":false},{\"signal\":\"Columna Check\",\"weight\":18,\"matched\":false},{\"signal\":\"Proceso para desarrollo\",\"weight\":22,\"matched\":false},{\"signal\":\"Procedimientos\",\"weight\":8,\"matched\":false},{\"signal\":\"Instrumentos\",\"weight\":8,\"matched\":false},{\"signal\":\"Códigos de tema\",\"weight\":10,\"matched\":false},{\"signal\":\"Códigos de indicador\",\"weight\":10,\"matched\":false}]}}},\"counts\":{\"unidades\":2,\"capacidades\":14,\"temas\":14,\"indicadores\":47}}}', 'PUBLICADO', NULL, 29, '2026-10-05 15:48:27', '2026-10-05 16:02:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_capacidades`
--

CREATE TABLE `plan_capacidades` (
  `id_capacidad` int(11) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `proceso_desarrollo` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plan_capacidades`
--

INSERT INTO `plan_capacidades` (`id_capacidad`, `id_unidad`, `descripcion`, `proceso_desarrollo`, `orden`, `created_at`, `updated_at`) VALUES
(16, 16, 'Describe la función de\nla Adm. Financiera\ndentro de la Empresa,\nsus aplicaciones y\nposibilidades de\ndesarrollo.', 'Febrero -\nMarzo', 1, '2026-08-31 14:14:56', '2026-08-31 14:14:56'),
(17, 17, 'Analiza un sistema informático, el ciclo de vida de un software y los términos empleados en el mismo.', 'Agosto a Septiembre', 1, '2026-08-31 14:48:56', '2026-08-31 14:48:56'),
(36, 36, 'Adquiere conocimientos sobre las funciones de las finanzas y reconoce la importancia de las empresas.', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(37, 37, 'Comprende la importancia de la registración contable y su implicancia en la Adm. Financiera.', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(38, 38, 'Describe el papel del Plan de Cuentas, Manual de Cuentas y documentos comerciales.', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(39, 39, 'Utiliza los libros contables exigidos por las leyes (Ley 1034/83) dentro de la Adm. Financiera.', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(40, 40, 'Conoce la aplicación del Impuesto al Valor Agregado (IVA) exigido por las leyes vigentes.', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(41, 41, 'Establece relaciones contables y maneja software para la obtención de informes financieros.', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(42, 42, 'Comprende la evolución histórica de la informática.', NULL, 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(43, 42, 'Identifica las partes de un sistema operativo', NULL, 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(44, 42, 'Utiliza diferentes herramientas de un sistema operativo', NULL, 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(45, 43, 'Reconoce el Microsoft Word como el procesador de texto más avanzado', NULL, 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(46, 43, 'Comprende el manejo de la ortografía en el procesador con las configuraciones de tipo de visualización', NULL, 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(47, 43, 'Utiliza las herramientas de la configuración general de las paginas', NULL, 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(48, 43, 'Utiliza los distintos objetos en Word', NULL, 4, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(49, 43, 'Identifica la seguridad de los documentos con sus estilos, hipervínculos y marcadores', NULL, 5, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(50, 43, 'Comprende el uso de combinar correspondencia', NULL, 6, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(51, 43, 'Identifica la utilización adecuada de las etiquetas con el manejo de las formulas y ecuaciones', NULL, 7, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(52, 43, 'Reconoce la utilización del documento maestro con la ventaja de las tablas de ilustración', NULL, 8, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(53, 43, 'Identifica el manejo del Macros y sus formularios', NULL, 9, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(54, 43, 'Comprende el modo guardado del documento', NULL, 10, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(55, 43, 'Utiliza el teclado de forma correcta', NULL, 11, '2026-10-05 15:48:27', '2026-10-05 15:48:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_indicadores`
--

CREATE TABLE `plan_indicadores` (
  `id_indicador` int(11) NOT NULL,
  `id_tema` int(11) NOT NULL,
  `codigo` varchar(32) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plan_indicadores`
--

INSERT INTO `plan_indicadores` (`id_indicador`, `id_tema`, `codigo`, `descripcion`, `orden`, `created_at`, `updated_at`) VALUES
(26, 16, NULL, 'Define la administración financiera y su importancia.', 1, '2026-08-31 14:16:07', '2026-08-31 14:16:07'),
(27, 17, NULL, 'Identifica las funciones del área informática en el soporte financiero.', 1, '2026-08-31 14:19:55', '2026-08-31 14:39:24'),
(28, 18, NULL, 'Describe el flujo de información en una empresa.', 1, '2026-08-31 14:21:16', '2026-08-31 14:21:16'),
(29, 19, NULL, 'Conoce los SDBD relacionales', 1, '2026-08-31 14:53:03', '2026-08-31 14:53:03'),
(60, 38, NULL, 'Define administración financiera.', 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(61, 38, NULL, 'Clasifica empresas según su actividad.', 2, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(62, 38, NULL, 'Identifica la relación entre finanzas e informática.', 3, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(63, 39, NULL, 'Explica la importancia de la contabilidad.', 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(64, 39, NULL, 'Aplica el principio de la partida doble.', 2, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(65, 39, NULL, 'Describe las etapas del ciclo contable.', 3, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(66, 40, NULL, 'Identifica documentos legales y comerciales.', 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(67, 40, NULL, 'Elabora un Plan de Cuentas en planilla electrónica.', 2, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(68, 40, NULL, 'Utiliza el manual de cuentas correctamente.', 3, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(69, 41, NULL, 'Registra operaciones en el Libro Diario.', 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(70, 41, NULL, 'Realiza pases al Libro Mayor de forma precisa.', 2, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(71, 41, NULL, 'Cumple con las formalidades de la Ley 1034/83.', 3, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(72, 42, NULL, 'Diferencia IVA Débito de IVA Crédito.', 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(73, 42, NULL, 'Liquida el IVA mensualmente.', 2, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(74, 42, NULL, 'Utiliza planillas para el registro de compras y ventas.', 3, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(75, 43, NULL, 'Elabora balance de comprobación de sumas y saldos.', 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(76, 43, NULL, 'Opera funciones contables en hojas de cálculo.', 2, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(77, 43, NULL, 'Obtiene informes básicos mediante software.', 3, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(78, 44, NULL, 'Identifica el concepto del ábaco', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(79, 44, NULL, 'Reconoce la creación de la calculadora', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(80, 44, NULL, 'Identifica las generaciones de la computadora', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(81, 45, NULL, 'Reconoce el hardware y sus partes', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(82, 45, NULL, 'Reconoce el software y sus tipos', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(83, 45, NULL, 'Conoce los tipo de computadoras', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(84, 46, NULL, 'Reconoce el entorno de Windows 10', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(85, 46, NULL, 'Visualiza el escritorio y sus partes', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(86, 46, NULL, 'Crea accesos directos en el escritorio', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(87, 46, NULL, 'Identifica el modo de organización de carpetas', 4, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(88, 46, NULL, 'Conoce el modo de organizar ventanas', 5, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(89, 47, NULL, 'Identifica el acceso al programa', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(90, 47, NULL, 'Reconoce las partes del programa', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(91, 47, NULL, 'Utiliza las diferentes formas de guardado', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(92, 47, NULL, 'Identifica el formato fuente', 4, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(93, 47, NULL, 'Identifica el formato párrafo', 5, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(94, 47, NULL, 'Reconoce el procedimiento de las', 6, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(95, 48, NULL, 'Conoce el cambio ortográfico', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(96, 48, NULL, 'Utiliza las tres configuraciones de la', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(97, 48, NULL, 'Identifica los tipos de visualización', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(98, 49, NULL, 'Conoce las ventajas de impresión', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(99, 49, NULL, 'Aplica el encabezado a las paginas', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(100, 49, NULL, 'Realiza los saltos de pagina', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(101, 49, NULL, 'Configura la numeración de pagina', 4, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(102, 50, NULL, 'Reconoce la inserción de imágenes', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(103, 50, NULL, 'Aplica las tablas con sus filas y', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(104, 50, NULL, 'Inserta distintas autoformas', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(105, 50, NULL, 'Diseña un organigrama con la', 4, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(106, 51, NULL, 'Conoce la contraseña de apertura', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(107, 51, NULL, 'Conoce la contraseña de Escritura', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(108, 51, NULL, 'Aplica el estilo al texto', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(109, 51, NULL, 'Utiliza los marcadores', 4, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(110, 51, NULL, 'Utiliza los hipervínculos', 5, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(111, 52, NULL, 'Conoce los pasos para crear una', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(112, 52, NULL, 'Diseña la planilla para la', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(113, 52, NULL, 'Diseña el certificado para la', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(114, 53, NULL, 'Aplica el proceso de la etiqueta', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(115, 53, NULL, 'Inserta las formulas en el documento', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(116, 54, NULL, 'Identifica el significado del documento maestro', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(117, 54, NULL, 'Crea un índice de tareas', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(118, 54, NULL, 'Crea un índice automático', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(119, 55, NULL, 'Identifica los pasos para crear un', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(120, 55, NULL, 'Conoce el modo de creación de', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(121, 56, NULL, 'Aplica el guardado del documento como HTML', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(122, 56, NULL, 'Identifica el modo de compartir un documento en pantalla', 2, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(123, 56, NULL, 'Aplica el guardado como PDF', 3, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(124, 57, NULL, 'Conoce la manera correcta de utilizar el teclado', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_temas`
--

CREATE TABLE `plan_temas` (
  `id_tema` int(11) NOT NULL,
  `id_capacidad` int(11) NOT NULL,
  `codigo` varchar(32) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text DEFAULT NULL,
  `horas_catedra` decimal(6,2) DEFAULT NULL,
  `tiempo_texto` text DEFAULT NULL,
  `fecha_texto` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plan_temas`
--

INSERT INTO `plan_temas` (`id_tema`, `id_capacidad`, `codigo`, `titulo`, `contenido`, `horas_catedra`, `tiempo_texto`, `fecha_texto`, `orden`, `created_at`, `updated_at`) VALUES
(16, 16, NULL, '1.1 El rol del administrador financiero en la era digital.', 'Primer tema de la primera unidad', 4.00, NULL, NULL, 1, '2026-08-31 14:15:49', '2026-08-31 14:40:02'),
(17, 16, NULL, '1.2 Interacción entre finanzas e informática.', 'Segundo tema  de la primera unidad', 4.00, NULL, NULL, 2, '2026-08-31 14:19:44', '2026-08-31 14:39:44'),
(18, 16, NULL, '1.3 Sistemas de información aplicados (ERP, CRM).', 'Tercer tema de la primera unidad', 4.00, NULL, NULL, 3, '2026-08-31 14:21:05', '2026-08-31 14:39:53'),
(19, 17, NULL, 'Sistema informático', 'Concepto.\n  - Importancia.\n  - Datos, seguridad, consistencia, integridad\n  - Componentes.\n    - Base de datos\n    - SGBD: concepto, programas de aplicación\n    - Dispositivos físicos (ordenadores, dispositivos de almacenamientos, etc.)\n    - Personal que utiliza y que desarrolla el sistema\n  - Ciclo de vida. Concepto, importancia, etapas y diferencia entre cada uno de\nellos\n  - Análisis del sistema. Concepto. Fases.', 16.00, NULL, NULL, 1, '2026-08-31 14:52:17', '2026-08-31 14:52:17'),
(38, 36, NULL, 'Finanzas personales, sociales y en crisis. La empresa como unidad económica. Eficiencia y Eficacia.', NULL, 8.00, '8 HC', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(39, 37, NULL, 'Concepto de Contabilidad. Partida Doble. Ciclo Contable. Importancia de la información para decidir.', NULL, 8.00, '8 HC', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(40, 38, NULL, 'Documentos comerciales (Factura, Recibo, Pagaré). Plan y Manual de Cuentas. Estructura técnica.', NULL, 12.00, '12 HC', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(41, 39, NULL, 'Libro Diario. Libro Inventario. Libro Mayor. Formalidades legales de los libros.', NULL, 20.00, '20 HC', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(42, 40, NULL, 'IVA (Ley 6380/19). Tasas (5% y 10%). Débito y Crédito Fiscal. Libro Compras y Ventas.', NULL, 12.00, '12 HC', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(43, 41, NULL, 'Balance de Comprobación de Sumas y Saldos. Introducción al Balance General. Microsoft Excel y Software Contable Open Source GNU Cash, HomeBank.', NULL, 20.00, '20 HC', NULL, 1, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(44, 42, NULL, 'Historia, evolución y generaciones', NULL, NULL, NULL, 'De marzo a junio', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(45, 43, NULL, 'Hardware Software Tipos de computadora', NULL, NULL, NULL, 'De marzo a junio', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(46, 44, NULL, 'Entorno de un sistema Operativo y sus fundamentos', NULL, NULL, NULL, 'De marzo a junio', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(47, 45, NULL, 'Acceso al programa de Word Entorno del programa Guardar Formato fuente Formato párrafo Numeración y viñetas', NULL, NULL, NULL, 'De marzo a junio', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(48, 46, NULL, 'Ortografía Configuración de Tipos de visualización de', NULL, NULL, NULL, 'De marzo a junio', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(49, 47, NULL, 'Impresión de documentos Saltos Encabezado y pie de Numeración de pagina', NULL, NULL, NULL, 'De marzo a junio', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(50, 48, NULL, 'Insertar imágenes, tablas, autoformas, organigramas y diagramas', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(51, 49, NULL, 'Seguridad de los documentos Estilos Marcadores Hipervínculos', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(52, 50, NULL, 'Combinación de correspondencia', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(53, 51, NULL, 'Etiquetas Formulas y Ecuaciones', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(54, 52, NULL, 'Documentos maestros Crear tablas de ilustración', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(55, 53, NULL, 'Macros Formularios', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(56, 54, NULL, 'HTML Compartir documentos PDF', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(57, 55, NULL, 'Dactilografía computarizada', NULL, NULL, NULL, 'De Julio a Noviembre', 1, '2026-10-05 15:48:27', '2026-10-05 15:48:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_tema_instrumentos`
--

CREATE TABLE `plan_tema_instrumentos` (
  `id_tema` int(11) NOT NULL,
  `id_instrumento` int(11) NOT NULL,
  `texto_fuente` varchar(150) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plan_tema_instrumentos`
--

INSERT INTO `plan_tema_instrumentos` (`id_tema`, `id_instrumento`, `texto_fuente`, `orden`) VALUES
(16, 4, NULL, 1),
(17, 1, NULL, 1),
(17, 7, NULL, 1),
(18, 1, NULL, 1),
(18, 6, NULL, 1),
(18, 7, NULL, 1),
(19, 1, NULL, 1),
(19, 2, NULL, 1),
(19, 4, NULL, 1),
(38, 1, 'Lista de Cotejo', 1),
(38, 2, 'RSA', 2),
(39, 3, 'Rúbrica', 1),
(40, 1, 'Lista de Cotejo', 1),
(40, 6, 'Portafolio', 2),
(41, 2, 'RSA', 2),
(41, 5, 'Prueba Escrita', 1),
(42, 4, 'Prueba Práctica', 1),
(43, 1, 'Rúbrica de Proyecto', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_tema_procedimientos`
--

CREATE TABLE `plan_tema_procedimientos` (
  `id_tema` int(11) NOT NULL,
  `id_procedimiento` int(11) NOT NULL,
  `texto_fuente` varchar(150) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plan_tema_procedimientos`
--

INSERT INTO `plan_tema_procedimientos` (`id_tema`, `id_procedimiento`, `texto_fuente`, `orden`) VALUES
(16, 1, NULL, 1),
(16, 4, NULL, 1),
(17, 2, NULL, 1),
(17, 4, NULL, 1),
(17, 7, NULL, 1),
(18, 2, NULL, 1),
(19, 1, NULL, 1),
(19, 7, NULL, 1),
(38, 2, 'Análisis de tareas', 2),
(38, 5, 'Observación', 1),
(39, 4, 'Prueba de desempeño', 1),
(40, 5, 'Observación', 1),
(41, 5, 'Ejercicios Prácticos', 1),
(42, 6, 'Resolución de problemas', 1),
(43, 7, 'Proyecto Integrador', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_tema_programacion`
--

CREATE TABLE `plan_tema_programacion` (
  `id_programacion` int(11) NOT NULL,
  `id_tema` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `horas_catedra_planificadas` decimal(6,2) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `plan_tema_programacion`
--

INSERT INTO `plan_tema_programacion` (`id_programacion`, `id_tema`, `fecha_inicio`, `fecha_fin`, `horas_catedra_planificadas`, `orden`, `observaciones`, `created_at`, `updated_at`) VALUES
(9, 16, '2026-08-31', '2026-08-31', 4.00, 1, 'Primer indicador del primer tema de la primera unidad', '2026-08-31 14:17:48', '2026-08-31 14:38:38'),
(10, 17, '2026-09-01', '2026-09-01', 4.00, 1, 'Primer indicador del segundo tema de la primera unidad', '2026-08-31 14:20:15', '2026-08-31 14:39:07'),
(11, 18, '2026-02-24', '2026-02-24', 4.00, 1, 'Primer indicador del tercer tema de la primera unidad', '2026-08-31 14:21:38', '2026-08-31 14:39:02'),
(12, 19, '2026-09-07', '2026-09-07', 9.00, 1, 'Observaciones del primer indicador del primer tema de la primera unidad', '2026-08-31 14:54:36', '2026-09-07 13:10:32'),
(25, 44, '2026-03-06', '2026-03-20', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(26, 45, '2026-03-27', '2026-04-10', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(27, 46, '2026-04-17', '2026-05-01', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(28, 47, '2026-05-08', '2026-05-22', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(29, 48, '2026-05-29', '2026-06-12', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(30, 49, '2026-06-19', '2026-06-26', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 2 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(31, 50, '2026-07-03', '2026-07-17', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(32, 51, '2026-07-24', '2026-08-07', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(33, 52, '2026-08-14', '2026-08-28', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(34, 53, '2026-09-04', '2026-09-18', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(35, 54, '2026-09-25', '2026-10-09', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(36, 55, '2026-10-16', '2026-10-30', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 3 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(37, 56, '2026-11-06', '2026-11-13', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 2 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(38, 57, '2026-11-20', '2026-11-27', NULL, 1, 'ESTIMADA a pedido del usuario el 2026-10-05. Horario vigente: viernes 08:20-09:00 (horario 243). Bloque de 2 viernes potenciales dentro del periodo fuente; sin comprobar feriados, recesos ni desarrollo historico real. Pendiente de validar con calendario academico.', '2026-10-05 15:58:11', '2026-10-05 15:58:11'),
(39, 19, '2026-10-05', '2026-10-05', NULL, 2, 'Programacion puntual para prueba fisica real del 2026-10-05, autorizada por el usuario. Tema: Sistema informatico. Horario real vigente de Danilo, Laboratorio Programacion, 3 BTI. Se conserva la programacion anterior.', '2026-10-05 16:11:07', '2026-10-05 16:11:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_unidades`
--

CREATE TABLE `plan_unidades` (
  `id_unidad` int(11) NOT NULL,
  `id_plan` int(11) NOT NULL,
  `codigo` varchar(32) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `horas_catedra` decimal(6,2) DEFAULT NULL,
  `tiempo_texto` text DEFAULT NULL,
  `proceso_texto` text DEFAULT NULL,
  `proceso_inicio` date DEFAULT NULL,
  `proceso_fin` date DEFAULT NULL,
  `area_transversal` text DEFAULT NULL,
  `metodologia` text DEFAULT NULL,
  `medios_verificacion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `plan_unidades`
--

INSERT INTO `plan_unidades` (`id_unidad`, `id_plan`, `codigo`, `nombre`, `descripcion`, `orden`, `horas_catedra`, `tiempo_texto`, `proceso_texto`, `proceso_inicio`, `proceso_fin`, `area_transversal`, `metodologia`, `medios_verificacion`, `created_at`, `updated_at`) VALUES
(16, 7, NULL, 'I. La Administración Financiera y la Información', 'Describe la función de\nla Adm. Financiera\ndentro de la Empresa,\nsus aplicaciones y\nposibilidades de\ndesarrollo.', 1, 12.00, NULL, NULL, '2026-08-24', '2026-09-05', 'Educación económica y financiera', 'Observación,\nAnálisis de\ntareas.', 'Lista de Cotejo,\nRSA (Registro\nde Secuencia\nde\nAprendizaje).', '2026-08-31 14:14:22', '2026-08-31 14:37:38'),
(17, 8, NULL, 'Analiza un sistema informático, el ciclo de vida de un software y los términos empleados en el mismo.', 'Descripción de la primera unidad de laboratorio informática', 1, 16.00, NULL, NULL, '2026-08-30', '2026-09-30', 'Informática', 'Digital', 'RSA, lista de cotejo', '2026-08-31 14:48:04', '2026-08-31 14:48:04'),
(36, 16, 'I', 'Fundamentos de las Finanzas y la Empresa', NULL, 1, 8.00, '8 HC', 'Marzo', NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(37, 16, 'II', 'El Proceso de Registración Contable', NULL, 2, 8.00, '8 HC', 'Abril', NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(38, 16, 'III', 'Organización y Soporte Documental', NULL, 3, 12.00, '12 HC', 'Abril - Mayo', NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(39, 16, 'IV', 'Libros Contables y Registración Legal', NULL, 4, 20.00, '20 HC', 'Junio - Agosto', NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(40, 16, 'V', 'Gestión Impositiva Aplicada', NULL, 5, 12.00, '12 HC', 'Septiembre', NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(41, 16, 'VI', 'Cierre de Ciclo y Herramientas Tecnológicas', NULL, 6, 20.00, '20 HC', 'Octubre - Noviembre', NULL, NULL, NULL, NULL, NULL, '2026-10-05 14:04:34', '2026-10-05 14:04:34'),
(42, 17, NULL, 'Manejo del sistema operativo avanzado', NULL, 1, NULL, NULL, 'De marzo a junio', NULL, NULL, 'Ambiental: la sala de informática se debe mantener en un Ambiente limpio y saludable', 'Clase Teórico con: Trabajo Dual', 'Libro de Informática y computadora', '2026-10-05 15:48:27', '2026-10-05 15:48:27'),
(43, 17, NULL, 'Uso avanzado de Microsoft Office Word', NULL, 2, NULL, NULL, 'De marzo a junio De Julio a Noviembre', NULL, NULL, 'Ambiental: la sala de informática se debe mantener en un Ambiente limpio y saludable Experiencia de Vida Cotidiana', 'Clase Teórico y Práctico con: Trabajo Dual Clase Teórico y Trabajo Grupal Clase Teórico y Práctico con: Trabajo Dual Trabajo Grupal Clase Teórico y Clase Teórico y Práctico con:', 'Libro de Informática Y computador Libro de R.S.A Prueba oral: Libro de Informática R.S.A Prueba escrita Libro de Y computador Libro de Y computador R.S.A Libro de Informática Libro de Informática y Computador R.S.A', '2026-10-05 15:48:27', '2026-10-05 15:48:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procedimientos_evaluativos`
--

CREATE TABLE `procedimientos_evaluativos` (
  `id_procedimiento` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `procedimientos_evaluativos`
--

INSERT INTO `procedimientos_evaluativos` (`id_procedimiento`, `nombre`, `activo`, `created_at`) VALUES
(1, 'Observacion', 1, '2026-08-31 13:26:23'),
(2, 'Analisis de tareas', 1, '2026-08-31 13:26:23'),
(3, 'Prueba escrita', 1, '2026-08-31 13:26:23'),
(4, 'Analisis de casos', 1, '2026-08-31 13:26:23'),
(5, 'Ejercicios practicos', 1, '2026-08-31 13:26:23'),
(6, 'Resolucion de problemas', 1, '2026-08-31 13:26:23'),
(7, 'Proyecto integrador', 1, '2026-08-31 13:26:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id_profesor` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula_identidad` varchar(20) NOT NULL,
  `huella_id` int(11) DEFAULT NULL,
  `user_id_global` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `pendiente_sync` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id_profesor`, `id_usuario`, `nombre`, `apellido`, `cedula_identidad`, `huella_id`, `user_id_global`, `fecha_registro`, `activo`, `pendiente_sync`) VALUES
(1, NULL, 'Juan Actualizado', 'Perez Actualizado', '1234567 ', 13, 'PROF_1', '2026-04-23 16:58:23', 0, 1),
(7, NULL, 'Nahuel', 'Montiel', '7392273', 11, 'PROF_7', '2026-06-08 16:33:40', 0, 1),
(8, NULL, 'Nahuel Actualizado', 'Perez Actualizado', '7392275 Actualizado', NULL, 'PROF_8', '2026-06-08 16:45:41', 0, 0),
(9, NULL, 'Nahuel Actualizado', 'Montaner Actualizado', '7392278 Actualizada', NULL, 'PROF_9', '2026-06-08 17:08:34', 0, 0),
(10, NULL, 'Nahuel', 'Montaner', '7392279', NULL, 'PROF_10', '2026-06-08 17:23:26', 0, 0),
(11, NULL, 'Nahuel Actualizado', 'Montaner Actualizado', '7392271', NULL, 'PROF_11', '2026-06-08 17:29:56', 0, 0),
(12, NULL, 'Pepe', 'Peron', '00001234', NULL, 'PROF_12', '2026-06-18 18:25:38', 0, 0),
(13, NULL, 'Nahuel', 'Montiel', '3216549', NULL, 'PROF_6a7df05801a0c_812', '2026-08-13 16:27:04', 0, 0),
(21, 28, 'Danilo', 'Franco', '6767676', NULL, 'PROF_6a872970109a6_428', '2026-08-20 16:21:04', 1, 0),
(22, NULL, 'Fabian', 'Quintana', '1233212', NULL, 'PROF_6a87298b87a30_386', '2026-08-20 16:21:31', 1, 0),
(23, NULL, 'Cesar', 'Ojeda', '0100020', NULL, 'PROF_6a8729a4066e0_605', '2026-08-20 16:21:56', 1, 0),
(24, NULL, 'Mirna', 'Gayoso', '789263', NULL, 'PROF_6a8729b852d6e_391', '2026-08-20 16:22:16', 1, 0),
(25, NULL, 'Adriana', 'Pineda', '670674', NULL, 'PROF_6a8729de02004_568', '2026-08-20 16:22:54', 1, 0),
(26, NULL, 'Alba', 'Marecos', '582833', NULL, 'PROF_6a8729f2ae2df_730', '2026-08-20 16:23:14', 1, 0),
(27, NULL, 'Ji Ae', 'Kim (Sabia)', '67929267', NULL, 'PROF_6a872a46285c6_820', '2026-08-20 16:24:38', 1, 0),
(28, NULL, 'Gladys', 'Correa', '1001010010', NULL, 'PROF_6a872a5a01b66_316', '2026-08-20 16:24:58', 1, 0),
(29, NULL, 'Paola', 'Lezcano', '323289', NULL, 'PROF_6a872b23495c2_757', '2026-08-20 16:28:19', 1, 0),
(30, NULL, 'Fatima', 'Vega', '9857757', NULL, 'PROF_6a872ba910035_161', '2026-08-20 16:30:33', 1, 0),
(31, NULL, 'Patricia', 'Vallejos', '873737', NULL, 'PROF_6a872bbd1180b_328', '2026-08-20 16:30:53', 1, 0),
(32, NULL, 'Sergio', 'Meza', '60150', NULL, 'PROF_6a872c1c8f0f1_850', '2026-08-20 16:32:28', 1, 0),
(33, NULL, 'Febe', 'Roa', '978002', NULL, 'PROF_6a872c7262c29_835', '2026-08-20 16:33:54', 1, 0),
(34, NULL, 'Raul', 'Alonso', '6015000', NULL, 'PROF_6a872d37b98eb_423', '2026-08-20 16:37:11', 1, 0),
(35, NULL, 'Rosalino', 'Fleitas', '5649821', NULL, 'PROF_6a872ea9dc6f8_358', '2026-08-20 16:43:21', 1, 0),
(36, NULL, 'Analia', 'Vera', '6891235', NULL, 'PROF_6a872f097f76e_742', '2026-08-20 16:44:57', 1, 0),
(37, NULL, 'Maria', 'Florentin', '4587632', NULL, 'PROF_6a872f25b5c54_413', '2026-08-20 16:45:25', 1, 0),
(38, NULL, 'Maria', 'Vallejos', '4238615', NULL, 'PROF_6a872f5271fda_905', '2026-08-20 16:46:10', 1, 0),
(39, NULL, 'Araceli', 'Romero', '521893', NULL, 'PROF_6a872faf65bea_408', '2026-08-20 16:47:43', 1, 0),
(40, NULL, 'Karen', 'Dominguez', '4782349', NULL, 'PROF_6a872fcc8b6a6_592', '2026-08-20 16:48:12', 1, 0),
(41, NULL, 'Alejandro', 'Vera', '2659375', NULL, 'PROF_6a872ff43977b_675', '2026-08-20 16:48:52', 1, 0),
(42, NULL, 'Rossana', 'Armoa', '4956278', NULL, 'PROF_6a87300e43270_478', '2026-08-20 16:49:18', 1, 0),
(45, NULL, 'Iara', 'Duarte', '694235', NULL, 'PROF_6a8730222f1b2_951', '2026-08-20 16:49:38', 1, 0),
(46, NULL, 'Carolina', 'Colman', '7561289', NULL, 'PROF_6a873047e0290_998', '2026-08-20 16:50:15', 1, 0),
(47, NULL, 'Jose', 'Ovelar', '756289', NULL, 'PROF_6a8730acb59b6_742', '2026-08-20 16:51:56', 1, 0),
(48, NULL, 'Hyelan', 'Kim', '1568943', NULL, 'PROF_6a8730d3eb459_452', '2026-08-20 16:52:35', 1, 0),
(49, NULL, 'Ingrid', 'Lopez', '672413', NULL, 'PROF_6a8730ee6bb44_869', '2026-08-20 16:53:02', 1, 0),
(56, NULL, 'RECESO', 'RECESO', '1212', NULL, 'PROF_6a873a4705f9d_273', '2026-08-20 17:32:55', 1, 0),
(63, NULL, 'Patricia', 'Ojeda', '5863279', NULL, 'PROF_6a8c3f259f940_721', '2026-08-24 12:55:01', 1, 0),
(80, NULL, 'Rita', 'Vallejos', '5863241.', NULL, 'PROF_6a8c76a71b836_619', '2026-08-24 16:51:51', 1, 0),
(83, NULL, 'Rosalia', 'Florentin', '9523641', NULL, 'PROF_6a90634d273c9_291', '2026-08-27 16:18:21', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registros_anecdoticos`
--

CREATE TABLE `registros_anecdoticos` (
  `id_registro` int(11) NOT NULL,
  `id_clase` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_asistencia_estudiante` int(11) DEFAULT NULL,
  `observacion` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_huella`
--

CREATE TABLE `solicitudes_huella` (
  `id_solicitud` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `estado` enum('PENDIENTE','EN_PROCESO','COMPLETADO','ERROR') DEFAULT 'PENDIENTE',
  `huella_id` int(11) DEFAULT NULL,
  `mensaje` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sync_biometrica`
--

CREATE TABLE `sync_biometrica` (
  `id_sync` int(11) NOT NULL,
  `id_huella` int(11) NOT NULL,
  `id_aula` int(11) DEFAULT NULL,
  `room_id` varchar(50) DEFAULT NULL,
  `estado` enum('PENDIENTE','ENVIADO','CONFIRMADO','ERROR') DEFAULT 'PENDIENTE',
  `intentos` int(11) DEFAULT 0,
  `mensaje` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sync_biometrica`
--

INSERT INTO `sync_biometrica` (`id_sync`, `id_huella`, `id_aula`, `room_id`, `estado`, `intentos`, `mensaje`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(49, 49, 18, NULL, 'ERROR', 1, 'DATOS_SYNC_INVALIDOS', '2026-08-10 18:24:33', '2026-08-10 18:25:22'),
(50, 50, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y confirmada por aula', '2026-08-10 18:25:21', '2026-08-13 18:02:12'),
(51, 51, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y confirmada por aula', '2026-08-13 18:02:08', '2026-08-13 18:04:18'),
(52, 52, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y confirmada por aula', '2026-08-13 18:04:12', '2026-08-17 12:26:35'),
(53, 53, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=3610232a', '2026-08-17 12:26:32', '2026-08-17 14:09:16'),
(54, 54, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=3793f281', '2026-08-17 14:09:12', '2026-08-17 14:23:41'),
(56, 56, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=acc9ad23', '2026-08-17 14:25:14', '2026-08-17 14:25:18'),
(57, 57, 18, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=e7fb18cb', '2026-08-17 14:33:14', '2026-08-17 14:33:20'),
(58, 58, 30, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=31237b54', '2026-08-31 15:09:47', '2026-09-07 14:53:53'),
(61, 61, 30, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=19992ec5', '2026-09-07 16:17:00', '2026-09-07 16:17:04'),
(62, 62, 30, NULL, 'CONFIRMADO', 1, 'Huella instalada y verificada por aula. CRC32=3eb0b7ef', '2026-09-07 17:01:05', '2026-09-07 17:01:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(80) NOT NULL,
  `celular` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('SuperAdmin','Coordinador','Profesor','Administracion') NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `apellido`, `usuario`, `email`, `celular`, `password`, `rol`, `fecha_creacion`, `activo`) VALUES
(28, 'Danilo', 'Franco', 'danilo', 'danilofranco906@gmail.com', 995684004, '$2y$10$ujB1Rc7NWKkMMX0OVXuMAeHQJbri52H1kBiOV6K6HOiKTD70NBODi', 'Profesor', '2026-08-31 14:29:12', 1),
(29, 'Admin', 'Principal', 'admin1', 'admin@test.com', 981234567, '$2y$10$cPm0PqykacBQYPXSO50XUeQH3CiPBt1J1fKlQwOy6l7mSUHFVtOPm', 'SuperAdmin', '2026-04-27 16:23:44', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignacion_docente`
--
ALTER TABLE `asignacion_docente`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD UNIQUE KEY `uq_asignacion_id_grado` (`id_asignacion`,`id_grado`),
  ADD UNIQUE KEY `uq_asignacion_activa_por_grado` (`id_profesor`,`id_materia`,`id_grado`,`anio_lectivo`,`clave_asignacion_activa`),
  ADD KEY `id_profesor` (`id_profesor`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `idx_asignacion_grado` (`id_grado`),
  ADD KEY `idx_asignacion_busqueda` (`id_profesor`,`id_materia`,`id_grado`,`anio_lectivo`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `fk_asistencia_estudiante` (`huella_id`);

--
-- Indices de la tabla `asistencias_estudiantes`
--
ALTER TABLE `asistencias_estudiantes`
  ADD PRIMARY KEY (`id_asistencia_estudiante`),
  ADD KEY `fk_asistencia_estudiante_id` (`id_estudiante`);

--
-- Indices de la tabla `asistencias_profesores`
--
ALTER TABLE `asistencias_profesores`
  ADD PRIMARY KEY (`id_asistencia_profesor`),
  ADD KEY `fk_asistencia_profesor_id` (`id_profesor`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id_aula`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `aula_huellas_sync`
--
ALTER TABLE `aula_huellas_sync`
  ADD PRIMARY KEY (`id_aula_sync`),
  ADD UNIQUE KEY `uq_aula_huellas_sync_id_sync` (`id_sync`);

--
-- Indices de la tabla `biometric_mapping`
--
ALTER TABLE `biometric_mapping`
  ADD PRIMARY KEY (`id_mapping`);

--
-- Indices de la tabla `clases_diarias`
--
ALTER TABLE `clases_diarias`
  ADD PRIMARY KEY (`id_clase`),
  ADD UNIQUE KEY `uq_clase_asignacion_fecha_hora` (`id_asignacion`,`fecha`,`hora_inicio`,`hora_fin`),
  ADD KEY `fk_clase_horario` (`id_horario`),
  ADD KEY `fk_clase_plan` (`id_plan`),
  ADD KEY `fk_clase_unidad` (`id_unidad`),
  ADD KEY `fk_clase_capacidad` (`id_capacidad`),
  ADD KEY `idx_clase_fecha` (`fecha`),
  ADD KEY `idx_clase_asignacion_fecha` (`id_asignacion`,`fecha`),
  ADD KEY `idx_clase_tema_fecha` (`id_tema`,`fecha`);

--
-- Indices de la tabla `clase_diaria_indicadores`
--
ALTER TABLE `clase_diaria_indicadores`
  ADD PRIMARY KEY (`id_clase`,`id_indicador`),
  ADD KEY `fk_clase_indicador_indicador` (`id_indicador`);

--
-- Indices de la tabla `configuracion_informes`
--
ALTER TABLE `configuracion_informes`
  ADD PRIMARY KEY (`id_configuracion`),
  ADD KEY `fk_config_informe_usuario` (`updated_by`),
  ADD KEY `idx_config_informe_activo` (`activo`,`updated_at`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD UNIQUE KEY `cedula_identidad` (`cedula_identidad`),
  ADD UNIQUE KEY `huella_id` (`huella_id`);

--
-- Indices de la tabla `eventos_asistencia`
--
ALTER TABLE `eventos_asistencia`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `idx_evento_aula_fecha` (`id_aula`,`timestamp_evento`);

--
-- Indices de la tabla `grados`
--
ALTER TABLE `grados`
  ADD PRIMARY KEY (`id_grado`),
  ADD UNIQUE KEY `uq_grado_id_aula` (`id_grado`,`id_aula`),
  ADD KEY `idx_grado_aula` (`id_aula`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `id_asignacion` (`id_asignacion`),
  ADD KEY `idx_horario_grado` (`id_grado`),
  ADD KEY `idx_horario_aula` (`id_aula`),
  ADD KEY `idx_horario_asignacion_grado` (`id_asignacion`,`id_grado`),
  ADD KEY `idx_horario_grado_aula` (`id_grado`,`id_aula`),
  ADD KEY `idx_horario_vinculado` (`id_horario_vinculado`),
  ADD KEY `idx_grupo_clase_conjunta` (`id_grupo_clase_conjunta`);

--
-- Indices de la tabla `huellas_templates`
--
ALTER TABLE `huellas_templates`
  ADD PRIMARY KEY (`id_huella`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `instrumentos_evaluativos`
--
ALTER TABLE `instrumentos_evaluativos`
  ADD PRIMARY KEY (`id_instrumento`),
  ADD UNIQUE KEY `uq_instrumento_nombre` (`nombre`),
  ADD KEY `idx_instrumento_activo` (`activo`,`nombre`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`);

--
-- Indices de la tabla `nodos_esp32`
--
ALTER TABLE `nodos_esp32`
  ADD PRIMARY KEY (`id_nodo`),
  ADD UNIQUE KEY `node_id` (`node_id`),
  ADD KEY `idx_nodos_esp32_room_activo` (`room_id`,`activo`),
  ADD KEY `idx_nodos_esp32_lora_activo` (`lora_id`,`activo`);

--
-- Indices de la tabla `planes_anuales`
--
ALTER TABLE `planes_anuales`
  ADD PRIMARY KEY (`id_plan`),
  ADD UNIQUE KEY `uq_plan_asignacion_anio` (`id_asignacion`,`anio`),
  ADD KEY `fk_plan_creador` (`created_by`),
  ADD KEY `idx_plan_estado_anio` (`estado`,`anio`),
  ADD KEY `idx_plan_asignacion_estado` (`id_asignacion`,`estado`);

--
-- Indices de la tabla `plan_capacidades`
--
ALTER TABLE `plan_capacidades`
  ADD PRIMARY KEY (`id_capacidad`),
  ADD KEY `idx_capacidad_unidad_orden` (`id_unidad`,`orden`);

--
-- Indices de la tabla `plan_indicadores`
--
ALTER TABLE `plan_indicadores`
  ADD PRIMARY KEY (`id_indicador`),
  ADD KEY `idx_indicador_tema_orden` (`id_tema`,`orden`);

--
-- Indices de la tabla `plan_temas`
--
ALTER TABLE `plan_temas`
  ADD PRIMARY KEY (`id_tema`),
  ADD KEY `idx_tema_capacidad_orden` (`id_capacidad`,`orden`);

--
-- Indices de la tabla `plan_tema_instrumentos`
--
ALTER TABLE `plan_tema_instrumentos`
  ADD PRIMARY KEY (`id_tema`,`id_instrumento`),
  ADD KEY `fk_tema_instrumento_catalogo` (`id_instrumento`);

--
-- Indices de la tabla `plan_tema_procedimientos`
--
ALTER TABLE `plan_tema_procedimientos`
  ADD PRIMARY KEY (`id_tema`,`id_procedimiento`),
  ADD KEY `fk_tema_procedimiento_catalogo` (`id_procedimiento`);

--
-- Indices de la tabla `plan_tema_programacion`
--
ALTER TABLE `plan_tema_programacion`
  ADD PRIMARY KEY (`id_programacion`),
  ADD UNIQUE KEY `uq_programacion_tema_periodo` (`id_tema`,`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_programacion_fecha` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_programacion_tema_orden` (`id_tema`,`orden`);

--
-- Indices de la tabla `plan_unidades`
--
ALTER TABLE `plan_unidades`
  ADD PRIMARY KEY (`id_unidad`),
  ADD KEY `idx_unidad_plan_orden` (`id_plan`,`orden`);

--
-- Indices de la tabla `procedimientos_evaluativos`
--
ALTER TABLE `procedimientos_evaluativos`
  ADD PRIMARY KEY (`id_procedimiento`),
  ADD UNIQUE KEY `uq_procedimiento_nombre` (`nombre`),
  ADD KEY `idx_procedimiento_activo` (`activo`,`nombre`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id_profesor`),
  ADD UNIQUE KEY `cedula_identidad` (`cedula_identidad`),
  ADD UNIQUE KEY `huella_id` (`huella_id`),
  ADD UNIQUE KEY `uq_profesor_usuario` (`id_usuario`);

--
-- Indices de la tabla `registros_anecdoticos`
--
ALTER TABLE `registros_anecdoticos`
  ADD PRIMARY KEY (`id_registro`),
  ADD UNIQUE KEY `uq_registro_clase_estudiante` (`id_clase`,`id_estudiante`),
  ADD KEY `fk_registro_asistencia` (`id_asistencia_estudiante`),
  ADD KEY `fk_registro_creador` (`created_by`),
  ADD KEY `idx_registro_estudiante` (`id_estudiante`),
  ADD KEY `idx_registro_clase` (`id_clase`);

--
-- Indices de la tabla `solicitudes_huella`
--
ALTER TABLE `solicitudes_huella`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `id_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `sync_biometrica`
--
ALTER TABLE `sync_biometrica`
  ADD PRIMARY KEY (`id_sync`),
  ADD KEY `id_huella` (`id_huella`),
  ADD KEY `idx_sync_aula_estado` (`id_aula`,`estado`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignacion_docente`
--
ALTER TABLE `asignacion_docente`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `asistencias_estudiantes`
--
ALTER TABLE `asistencias_estudiantes`
  MODIFY `id_asistencia_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `asistencias_profesores`
--
ALTER TABLE `asistencias_profesores`
  MODIFY `id_asistencia_profesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id_aula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT de la tabla `aula_huellas_sync`
--
ALTER TABLE `aula_huellas_sync`
  MODIFY `id_aula_sync` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `biometric_mapping`
--
ALTER TABLE `biometric_mapping`
  MODIFY `id_mapping` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `clases_diarias`
--
ALTER TABLE `clases_diarias`
  MODIFY `id_clase` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_informes`
--
ALTER TABLE `configuracion_informes`
  MODIFY `id_configuracion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT de la tabla `eventos_asistencia`
--
ALTER TABLE `eventos_asistencia`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `grados`
--
ALTER TABLE `grados`
  MODIFY `id_grado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `huellas_templates`
--
ALTER TABLE `huellas_templates`
  MODIFY `id_huella` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `instrumentos_evaluativos`
--
ALTER TABLE `instrumentos_evaluativos`
  MODIFY `id_instrumento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT de la tabla `nodos_esp32`
--
ALTER TABLE `nodos_esp32`
  MODIFY `id_nodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `planes_anuales`
--
ALTER TABLE `planes_anuales`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plan_capacidades`
--
ALTER TABLE `plan_capacidades`
  MODIFY `id_capacidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `plan_indicadores`
--
ALTER TABLE `plan_indicadores`
  MODIFY `id_indicador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de la tabla `plan_temas`
--
ALTER TABLE `plan_temas`
  MODIFY `id_tema` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `plan_tema_programacion`
--
ALTER TABLE `plan_tema_programacion`
  MODIFY `id_programacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plan_unidades`
--
ALTER TABLE `plan_unidades`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `procedimientos_evaluativos`
--
ALTER TABLE `procedimientos_evaluativos`
  MODIFY `id_procedimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_profesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de la tabla `registros_anecdoticos`
--
ALTER TABLE `registros_anecdoticos`
  MODIFY `id_registro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `solicitudes_huella`
--
ALTER TABLE `solicitudes_huella`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `sync_biometrica`
--
ALTER TABLE `sync_biometrica`
  MODIFY `id_sync` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignacion_docente`
--
ALTER TABLE `asignacion_docente`
  ADD CONSTRAINT `asignacion_docente_ibfk_1` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`),
  ADD CONSTRAINT `asignacion_docente_ibfk_2` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  ADD CONSTRAINT `fk_asignacion_grado` FOREIGN KEY (`id_grado`) REFERENCES `grados` (`id_grado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `fk_asistencia_estudiante` FOREIGN KEY (`huella_id`) REFERENCES `estudiantes` (`huella_id`),
  ADD CONSTRAINT `fk_asistencia_profesores` FOREIGN KEY (`huella_id`) REFERENCES `profesores` (`huella_id`);

--
-- Filtros para la tabla `asistencias_estudiantes`
--
ALTER TABLE `asistencias_estudiantes`
  ADD CONSTRAINT `fk_asistencia_estudiante_id` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`);

--
-- Filtros para la tabla `asistencias_profesores`
--
ALTER TABLE `asistencias_profesores`
  ADD CONSTRAINT `fk_asistencia_profesor_id` FOREIGN KEY (`id_profesor`) REFERENCES `profesores` (`id_profesor`);

--
-- Filtros para la tabla `clases_diarias`
--
ALTER TABLE `clases_diarias`
  ADD CONSTRAINT `fk_clase_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_docente` (`id_asignacion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_capacidad` FOREIGN KEY (`id_capacidad`) REFERENCES `plan_capacidades` (`id_capacidad`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_horario` FOREIGN KEY (`id_horario`) REFERENCES `horarios` (`id_horario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_plan` FOREIGN KEY (`id_plan`) REFERENCES `planes_anuales` (`id_plan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_tema` FOREIGN KEY (`id_tema`) REFERENCES `plan_temas` (`id_tema`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `plan_unidades` (`id_unidad`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `clase_diaria_indicadores`
--
ALTER TABLE `clase_diaria_indicadores`
  ADD CONSTRAINT `fk_clase_indicador_clase` FOREIGN KEY (`id_clase`) REFERENCES `clases_diarias` (`id_clase`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_indicador_indicador` FOREIGN KEY (`id_indicador`) REFERENCES `plan_indicadores` (`id_indicador`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `configuracion_informes`
--
ALTER TABLE `configuracion_informes`
  ADD CONSTRAINT `fk_config_informe_usuario` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `grados`
--
ALTER TABLE `grados`
  ADD CONSTRAINT `fk_grado_aula` FOREIGN KEY (`id_aula`) REFERENCES `aulas` (`id_aula`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `fk_horario_asignacion_grado` FOREIGN KEY (`id_asignacion`,`id_grado`) REFERENCES `asignacion_docente` (`id_asignacion`, `id_grado`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horario_grado_aula` FOREIGN KEY (`id_grado`,`id_aula`) REFERENCES `grados` (`id_grado`, `id_aula`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_horario_vinculado` FOREIGN KEY (`id_horario_vinculado`) REFERENCES `horarios` (`id_horario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_docente` (`id_asignacion`);

--
-- Filtros para la tabla `huellas_templates`
--
ALTER TABLE `huellas_templates`
  ADD CONSTRAINT `huellas_templates_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`);

--
-- Filtros para la tabla `planes_anuales`
--
ALTER TABLE `planes_anuales`
  ADD CONSTRAINT `fk_plan_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_docente` (`id_asignacion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_plan_creador` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_capacidades`
--
ALTER TABLE `plan_capacidades`
  ADD CONSTRAINT `fk_capacidad_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `plan_unidades` (`id_unidad`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_indicadores`
--
ALTER TABLE `plan_indicadores`
  ADD CONSTRAINT `fk_indicador_tema` FOREIGN KEY (`id_tema`) REFERENCES `plan_temas` (`id_tema`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_temas`
--
ALTER TABLE `plan_temas`
  ADD CONSTRAINT `fk_tema_capacidad` FOREIGN KEY (`id_capacidad`) REFERENCES `plan_capacidades` (`id_capacidad`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_tema_instrumentos`
--
ALTER TABLE `plan_tema_instrumentos`
  ADD CONSTRAINT `fk_tema_instrumento_catalogo` FOREIGN KEY (`id_instrumento`) REFERENCES `instrumentos_evaluativos` (`id_instrumento`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tema_instrumento_tema` FOREIGN KEY (`id_tema`) REFERENCES `plan_temas` (`id_tema`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_tema_procedimientos`
--
ALTER TABLE `plan_tema_procedimientos`
  ADD CONSTRAINT `fk_tema_procedimiento_catalogo` FOREIGN KEY (`id_procedimiento`) REFERENCES `procedimientos_evaluativos` (`id_procedimiento`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tema_procedimiento_tema` FOREIGN KEY (`id_tema`) REFERENCES `plan_temas` (`id_tema`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_tema_programacion`
--
ALTER TABLE `plan_tema_programacion`
  ADD CONSTRAINT `fk_programacion_tema` FOREIGN KEY (`id_tema`) REFERENCES `plan_temas` (`id_tema`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `plan_unidades`
--
ALTER TABLE `plan_unidades`
  ADD CONSTRAINT `fk_unidad_plan` FOREIGN KEY (`id_plan`) REFERENCES `planes_anuales` (`id_plan`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `fk_profesor_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `registros_anecdoticos`
--
ALTER TABLE `registros_anecdoticos`
  ADD CONSTRAINT `fk_registro_asistencia` FOREIGN KEY (`id_asistencia_estudiante`) REFERENCES `asistencias_estudiantes` (`id_asistencia_estudiante`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registro_clase` FOREIGN KEY (`id_clase`) REFERENCES `clases_diarias` (`id_clase`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registro_creador` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registro_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `sync_biometrica`
--
ALTER TABLE `sync_biometrica`
  ADD CONSTRAINT `sync_biometrica_ibfk_1` FOREIGN KEY (`id_huella`) REFERENCES `huellas_templates` (`id_huella`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
