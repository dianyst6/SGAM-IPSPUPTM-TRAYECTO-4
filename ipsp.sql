-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-11-2026 a las 21:36:52
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
-- Base de datos: `ipsp`
--
CREATE DATABASE IF NOT EXISTS `ipsp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ipsp`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `afiliados`
--

CREATE TABLE `afiliados` (
  `ID` int(11) NOT NULL,
  `id_persona` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `afiliados`
--

INSERT INTO `afiliados` (`ID`, `id_persona`, `created_at`, `updated_at`) VALUES
(52, 6, '2026-03-28 00:58:32', '2026-11-11 16:49:12'),
(54, 1, '2026-05-14 23:17:52', '2026-11-11 16:11:13'),
(55, 5, '2026-06-02 02:42:38', '2026-11-11 16:11:13'),
(56, 3, '2026-06-02 12:28:58', '2026-11-11 16:11:13'),
(57, 7, '2026-06-24 22:53:10', '2026-11-11 16:11:13'),
(58, 9, '2026-11-11 16:26:57', '2026-11-11 16:26:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `beneficiarios`
--

CREATE TABLE `beneficiarios` (
  `id_beneficiario` int(11) NOT NULL,
  `id_persona` int(11) DEFAULT NULL,
  `id_afiliado` int(20) NOT NULL,
  `parentesco` enum('Hijo','Esposo/a','Padre','Madre','Otro') NOT NULL DEFAULT 'Otro',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `beneficiarios`
--

INSERT INTO `beneficiarios` (`id_beneficiario`, `id_persona`, `id_afiliado`, `parentesco`, `created_at`, `updated_at`) VALUES
(19, 2, 52, 'Otro', '2026-03-28 15:11:11', '2026-11-12 15:01:08'),
(21, 8, 52, 'Hijo', '2026-05-28 20:28:09', '2026-11-11 16:11:13'),
(22, 4, 52, 'Hijo', '2026-06-01 23:46:09', '2026-11-11 16:11:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `idbitacora` int(11) NOT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`idbitacora`, `usuario`, `accion`, `descripcion`, `fecha`) VALUES
(1, NULL, 'Registro de Beneficiario', 'Cédula: 22657594, Nombre: Luis, Apellido: Mendoza', '2026-04-30 22:36:32'),
(2, 'grego', 'Registro de Cita', 'Se ha registrado una cita de tipo Afiliado para la fecha: 2026-05-08T12:00', '2026-05-08 01:09:33'),
(3, 'grego', 'Registro Integral', 'Afiliado y Plan creados: 3771659', '2026-05-14 22:58:58'),
(4, 'grego', 'Registro Integral', 'Afiliado y Plan creados: 3771659', '2026-05-14 23:17:52'),
(5, 'grego', 'Asignación de Plan', 'Nuevo plan (7) asignado al afiliado: 3771659', '2026-05-18 23:50:23'),
(6, 'grego', 'Eliminación de Beneficiario y Citas', 'Se eliminó al beneficiario: Luis Mendoza (Cédula: 22657594) y todas las citas relacionadas.', '2026-05-18 23:51:17'),
(7, 'grego', 'Asignación de Plan', 'Nuevo plan (7) asignado al afiliado: 30270492', '2026-05-19 21:46:33'),
(8, NULL, 'Registro de Beneficiario', 'Cédula: 34234140, Nombre: Sebastian, Apellido: Pérez', '2026-05-28 20:28:09'),
(9, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Comunidad UPTM (Externo) para la fecha: 2026-06-04T07:41', '2026-05-28 20:38:28'),
(10, 'admin', 'Pago Registrado', 'Se procesó pago de cita #69 por 17 $.', '2026-05-28 20:49:24'),
(11, 'admin', 'Pago Cita con Póliza', 'Cita #68 pagada mediante descuento de póliza. Monto original: 20.00 $, Monto descontado: 20.00 $.', '2026-05-28 20:50:17'),
(12, 'grego', 'Registro de Beneficiario', 'Cédula: 22657594, Nombre: Luis , Apellido: Mendoza', '2026-06-01 23:46:09'),
(13, 'grego', 'Inicio de Sesión', 'El usuario inició sesión en el sistema', '2026-06-01 23:54:36'),
(14, 'grego', 'Inicio de Sesión', 'El usuario inició sesión en el sistema', '2026-06-01 23:54:52'),
(16, 'grego', 'Registro de Comunidad UPTM', 'Se registró a la persona en comunidad: liz perez (C.I: 12308796)', '2026-06-02 00:22:41'),
(17, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 12308793 (Jose Pérez)', '2026-06-02 00:22:57'),
(18, 'grego', 'Registro de Comunidad UPTM', 'Se registró a la persona en comunidad: dianys torres (C.I: 30960254)', '2026-06-02 00:35:45'),
(19, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 30960254 (dianys torres)', '2026-06-02 00:36:00'),
(20, 'grego', 'Registro de Comunidad UPTM', 'Se registró a la persona en comunidad: diana torrega (C.I: 30960255)', '2026-06-02 00:37:59'),
(21, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 30960255 (diana torrega)', '2026-06-02 00:40:36'),
(22, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 30960255 (diana torrega)', '2026-06-02 00:42:49'),
(23, 'grego', 'Registro de Comunidad UPTM', 'Se registró a la persona en comunidad: grecio salazar (C.I: 30270491)', '2026-06-02 00:44:05'),
(24, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 30270491 (grecio salazar)', '2026-06-02 00:44:45'),
(25, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 30960255 (diana torrega)', '2026-06-02 00:46:05'),
(26, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 30960255 (diana torrega)', '2026-06-02 00:46:19'),
(27, 'grego', 'Registro de Comunidad UPTM', 'Se registró a la persona en comunidad: genesis contreras (C.I: 28296254)', '2026-06-02 00:57:01'),
(28, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 28296254 (genesis contreras)', '2026-06-02 00:57:11'),
(29, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 28296254 (genesis leimar contreras)', '2026-06-02 00:57:28'),
(30, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 28296254 (genesis leimar contreras)', '2026-06-02 00:57:46'),
(33, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 12308793 (Jose Pérez)', '2026-06-02 01:30:25'),
(34, 'grego', 'Edición de Comunidad UPTM', 'Se actualizaron los datos del externo C.I: 22657594 (Luis Mendoza)', '2026-06-02 01:30:30'),
(36, 'grego', 'Eliminación de usuario', 'Se eliminó al usuario: medico1 y sus respuestas de seguridad.', '2026-06-02 02:03:06'),
(37, 'grego', 'Eliminación de usuario', 'Se eliminó al usuario: medico y sus respuestas de seguridad.', '2026-06-02 02:03:12'),
(38, 'grego', 'Eliminación de usuario', 'Se eliminó al usuario: medico2 y sus respuestas de seguridad.', '2026-06-02 02:03:17'),
(39, 'odontologo', 'Registro de Historia', 'Paciente CI: 3771659 (afiliado)', '2026-06-02 02:32:13'),
(40, 'grego', 'Registro Integral', 'Afiliado y Plan creados: 28296254', '2026-06-02 02:42:38'),
(41, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Afiliado para la fecha: 2026-06-21T14:02', '2026-06-02 02:58:32'),
(42, 'admin', 'Pago Cita con Póliza', 'Cita #70 pagada mediante descuento de póliza. Monto original: 20.00 $, Monto descontado: 12.00 $.', '2026-06-02 02:58:53'),
(43, 'admin', 'Registro Integral', 'Afiliado y Plan creados: 12345678', '2026-06-02 12:28:58'),
(44, 'admin', 'Registro Integral', 'Afiliado y Plan creados: 30492270', '2026-06-24 22:53:10'),
(45, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: oftalmologo y sus respuestas de seguridad.', '2026-07-12 00:11:36'),
(46, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: grexz y sus respuestas de seguridad.', '2026-07-12 00:11:40'),
(47, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: medico y sus respuestas de seguridad.', '2026-07-12 00:11:42'),
(48, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: grexzs y sus respuestas de seguridad.', '2026-07-12 00:11:43'),
(49, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: calco y sus respuestas de seguridad.', '2026-07-12 00:11:45'),
(50, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: calco2 y sus respuestas de seguridad.', '2026-07-12 00:11:53'),
(51, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: asdfs y sus respuestas de seguridad.', '2026-07-12 00:11:56'),
(52, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: calco01 y sus respuestas de seguridad.', '2026-07-12 00:11:58'),
(53, 'admin', 'Eliminación de usuario', 'Se eliminó al usuario: medicoso y sus respuestas de seguridad.', '2026-07-12 00:12:00'),
(55, 'admin', 'Edición de Afiliado', 'Se han actualizado los datos del afiliado con cédula: 30270492, Nombre: Gregory, Apellido: Pérezz', '2026-11-11 16:23:49'),
(56, 'admin', 'Registro Integral', 'Afiliado y Plan creados: 90000001', '2026-11-11 16:26:57'),
(57, 'admin', 'Edición de Afiliado', 'Se cambió la cédula del afiliado de 30270492 a 30270422. Nombre: Gregory, Apellido: Pérezz', '2026-11-11 16:49:12'),
(58, 'admin', 'Actualización de Beneficiario', 'Se cambió la cédula del beneficiario de 10505948 a 10505944. Nombre: Teresa Pérez', '2026-11-12 15:01:08'),
(59, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Comunidad UPTM (Externo) para la fecha: 2026-11-19T16:04', '2026-11-12 20:04:49'),
(60, 'admin', 'Pago Registrado', 'Se procesó pago de cita #71 por 18 $.', '2026-11-12 20:05:15'),
(61, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Afiliado para la fecha: 2026-11-26T20:36', '2026-11-12 20:36:29'),
(62, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Comunidad UPTM (Externo) para la fecha: 2026-12-01T16:37', '2026-11-12 20:37:13'),
(63, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Afiliado para la fecha: 2027-02-20T10:00', '2026-11-15 17:25:30'),
(64, 'admin', 'Pago Cita con Póliza', 'Cita #74 pagada mediante descuento de póliza. Monto original: $20.00, Monto descontado: $20.00, Descuento: 0.00%.', '2026-11-15 17:25:31'),
(66, 'admin', 'Cancelación de Cita', 'Se canceló la cita: consulta (ID Cita: 72), Paciente: Gregory Pérezz (Afiliado), Fecha: 2026-11-26 20:36:00, Especialidad: Ginecología', '2026-11-15 17:31:16'),
(67, 'admin', 'Cancelación de Cita', 'Se canceló la cita: consulta (ID Cita: 72), Paciente: Gregory Pérezz (Afiliado), Fecha: 2026-11-26 20:36:00, Especialidad: Ginecología', '2026-11-15 17:31:16'),
(68, 'admin', 'Cancelación de Cita', 'Se canceló la cita: endoscopia (ID Cita: 73), Paciente: Jose Pérez (Comunidad UPTM), Fecha: 2026-12-01 16:37:00, Especialidad: Gastroenterología', '2026-11-15 17:31:19'),
(69, 'admin', 'Cancelación de Cita', 'Se canceló la cita: endoscopia (ID Cita: 73), Paciente: Jose Pérez (Comunidad UPTM), Fecha: 2026-12-01 16:37:00, Especialidad: Gastroenterología', '2026-11-15 17:31:19'),
(77, 'admin', 'Asignaci¾n de Plan', 'Contrato 2026 creado para cÚdula 30270422 (inscripci¾n tardÝa del a±o en curso). Inicial: $54.00, cuota: $126.00 x 1', '2026-11-17 19:51:30'),
(78, 'admin', 'Pago de contrato', 'Contrato 1: Pago inicial completo por $54.00 (Bs 49,450.50 a tasa 915.75)', '2026-11-17 19:51:30'),
(79, 'admin', 'Pago de contrato', 'Contrato 1: 1 cuota(s) completa(s) por $126.00 (Bs 115,384.50 a tasa 915.75)', '2026-11-17 19:52:52'),
(80, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Afiliado para la fecha: 2026-11-26T08:12', '2026-11-17 20:08:32'),
(81, 'admin', 'Registro de Cita', 'Se ha registrado una cita de tipo Beneficiario para la fecha: 2026-11-22T16:08', '2026-11-17 20:09:07'),
(82, 'admin', 'Pago Cita con Póliza', 'Cita #76 pagada mediante descuento de póliza. Monto original: $20.00, Monto descontado: $20.00, Descuento: 0.00%.', '2026-11-17 20:09:27'),
(83, 'admin', 'Pago Cita con Póliza', 'Cita #77 pagada mediante descuento de póliza. Monto original: $20.00, Monto descontado: $20.00, Descuento: 0.00%.', '2026-11-17 20:09:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_examenes`
--

CREATE TABLE `categorias_examenes` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `monto_maximo_cobertura` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_examenes`
--

INSERT INTO `categorias_examenes` (`id_categoria`, `nombre_categoria`, `descripcion`, `monto_maximo_cobertura`) VALUES
(1, 'Consultas', 'Todas las consultas médicas generales y especializadas', 0.00),
(3, 'Estudios Ecograficos', '', 0.00),
(4, 'Rayos X', '', 0.00),
(5, 'Procedimientos Medicos', '', 0.00),
(6, 'Procedimientos especiales', '', 0.00),
(7, 'Odontologia', '', 0.00),
(8, 'Emergencia Ambulatoria', '', 0.00),
(9, 'Laboratorio', '', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `fecha_cita` datetime NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado_pago` enum('Por Pagar','Pagada','Deducida de Póliza') NOT NULL DEFAULT 'Por Pagar',
  `estado` enum('activa','cancelada') DEFAULT 'activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id_cita`, `id_especialidad`, `id_persona`, `fecha_cita`, `descripcion`, `estado_pago`, `estado`, `created_at`, `updated_at`) VALUES
(68, 2, 6, '2026-05-08 12:00:00', 'asd', 'Deducida de Póliza', 'cancelada', '2026-05-08 01:09:33', '2026-11-15 13:52:12'),
(69, 5, 10, '2026-06-04 07:41:00', 'CITA', 'Pagada', 'activa', '2026-05-28 20:38:28', '2026-11-12 20:33:10'),
(70, 7, 1, '2026-06-21 14:02:00', 'asd', 'Deducida de Póliza', 'cancelada', '2026-06-02 02:58:32', '2026-11-15 13:52:12'),
(71, 5, 10, '2026-11-19 16:04:00', 'consulta', 'Pagada', 'activa', '2026-11-12 20:04:49', '2026-11-12 20:33:10'),
(72, 1, 6, '2026-11-26 20:36:00', 'consulta', 'Por Pagar', 'cancelada', '2026-11-12 20:36:29', '2026-11-15 17:31:16'),
(73, 5, 10, '2026-12-01 16:37:00', 'endoscopia', 'Por Pagar', 'cancelada', '2026-11-12 20:37:13', '2026-11-15 17:31:19'),
(76, 6, 6, '2026-11-26 08:12:00', 'rayos x', 'Deducida de Póliza', 'activa', '2026-11-17 20:08:32', '2026-11-17 20:09:27'),
(77, 2, 4, '2026-11-22 16:08:00', 'consulta', 'Deducida de Póliza', 'activa', '2026-11-17 20:09:07', '2026-11-17 20:09:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas_examenes`
--

CREATE TABLE `citas_examenes` (
  `id_cita_examen` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `precio_historico` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas_examenes`
--

INSERT INTO `citas_examenes` (`id_cita_examen`, `id_cita`, `id_examen`, `precio_historico`) VALUES
(29, 68, 5, 20.00),
(30, 69, 4, 20.00),
(31, 70, 13, 20.00),
(32, 71, 4, 20.00),
(33, 72, 1, 20.00),
(34, 73, 12, 50.00),
(37, 76, 11, 20.00),
(38, 77, 5, 20.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `componentes_planes`
--

CREATE TABLE `componentes_planes` (
  `ID_componenteplan` int(11) NOT NULL,
  `ID_planes_componentes` int(11) NOT NULL,
  `ID_examen_componentes` int(11) DEFAULT NULL,
  `id_categoria_componente` int(11) DEFAULT NULL,
  `cantidad_maxima` int(11) DEFAULT NULL,
  `monto_maximo` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `componentes_planes`
--

INSERT INTO `componentes_planes` (`ID_componenteplan`, `ID_planes_componentes`, `ID_examen_componentes`, `id_categoria_componente`, `cantidad_maxima`, `monto_maximo`) VALUES
(24, 6, NULL, 1, 8, 300.00),
(25, 6, NULL, 3, 4, 80.00),
(26, 6, NULL, 4, NULL, 60.00),
(27, 6, NULL, 6, NULL, 60.00),
(28, 6, NULL, 7, NULL, 0.00),
(29, 6, NULL, 8, NULL, 300.00),
(30, 7, NULL, 1, 10, 300.00),
(31, 7, NULL, 9, NULL, 200.00),
(32, 7, NULL, 3, 3, 100.00),
(40, 9, NULL, 1, 8, 300.00),
(41, 9, NULL, 3, 4, 80.00),
(42, 9, NULL, 4, NULL, 60.00),
(43, 9, NULL, 6, NULL, 60.00),
(44, 9, NULL, 7, NULL, 0.00),
(45, 9, NULL, 8, NULL, 300.00),
(46, 9, NULL, 9, NULL, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consumo_plan`
--

CREATE TABLE `consumo_plan` (
  `ID_consumo` int(11) NOT NULL,
  `ID_contrato_plan` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_cita` int(11) DEFAULT NULL,
  `ID_examen_plan` int(11) DEFAULT NULL,
  `nombre_estudio_externo` varchar(255) DEFAULT NULL,
  `id_categoria_externa` int(11) DEFAULT NULL,
  `monto_descontado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_consumo` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `consumo_plan`
--

INSERT INTO `consumo_plan` (`ID_consumo`, `ID_contrato_plan`, `id_persona`, `id_cita`, `ID_examen_plan`, `nombre_estudio_externo`, `id_categoria_externa`, `monto_descontado`, `fecha_consumo`) VALUES
(1, 1, 6, 76, 11, NULL, NULL, 20.00, '2026-11-17 16:09:27'),
(2, 1, 4, 77, 5, NULL, NULL, 20.00, '2026-11-17 16:09:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contrato_cuotas`
--

CREATE TABLE `contrato_cuotas` (
  `id_cuota` int(11) NOT NULL,
  `id_contrato` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `id_pago` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contrato_cuotas`
--

INSERT INTO `contrato_cuotas` (`id_cuota`, `id_contrato`, `numero_cuota`, `anio`, `mes`, `fecha_vencimiento`, `monto`, `monto_pagado`, `estado`, `id_pago`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, 12, '2026-12-05', 126.00, 126.00, 'Pagada', 2, '2026-11-17 19:51:10', '2026-11-17 19:52:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contrato_plan`
--

CREATE TABLE `contrato_plan` (
  `ID_contrato` int(11) NOT NULL,
  `ID_planes_contrato` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `anio` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `frecuencia_pago` varchar(50) DEFAULT NULL,
  `dia_pago_mensual` int(11) DEFAULT NULL,
  `estado_contrato` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contrato_plan`
--

INSERT INTO `contrato_plan` (`ID_contrato`, `ID_planes_contrato`, `id_persona`, `anio`, `fecha_inicio`, `fecha_fin`, `monto_total`, `frecuencia_pago`, `dia_pago_mensual`, `estado_contrato`) VALUES
(1, 6, 6, 2026, '2026-11-17', '2026-12-31', 180.00, 'Mensual', 5, 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id_especialidad` int(11) NOT NULL,
  `nombre_especialidad` varchar(50) NOT NULL,
  `descuento` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id_especialidad`, `nombre_especialidad`, `descuento`) VALUES
(1, 'Ginecología', 0.00),
(2, 'Medicina interna', 0.00),
(3, 'Odontología', 30.00),
(4, 'Oftamología', 50.00),
(5, 'Gastroenterología', 0.00),
(6, 'Imagenología', 0.00),
(7, 'Laboratorio', 40.00),
(8, 'Urologia', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes`
--

CREATE TABLE `examenes` (
  `ID_examen` int(11) NOT NULL,
  `nombre_examen` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ID_especialidad_examenes` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examenes`
--

INSERT INTO `examenes` (`ID_examen`, `nombre_examen`, `precio`, `ID_especialidad_examenes`, `id_categoria`, `estado`) VALUES
(1, 'Consulta Ginecológica', 20.00, 1, 1, 'activo'),
(2, 'Consulta Oftalmologia', 40.00, 4, 1, 'activo'),
(4, 'Consulta gatroenterologia', 20.00, 5, 1, 'activo'),
(5, 'Consulta Medicina interna', 20.00, 2, 1, 'activo'),
(6, 'Ecografa Abdominal', 35.00, 6, 3, 'activo'),
(7, 'Ecografa Plvica', 30.00, 6, 3, 'activo'),
(8, 'Ecografa Renal', 40.00, 6, 3, 'activo'),
(9, 'Rayos X de Trax', 25.00, 6, 4, 'activo'),
(10, 'Rayos X de Columna Cervical', 50.00, 6, 4, 'activo'),
(11, 'Rayos X de Miembro Inferior', 20.00, 6, 4, 'activo'),
(12, 'Endoscopia', 50.00, 5, 6, 'activo'),
(13, 'Glicemia post pandrial', 20.00, 7, 9, 'activo'),
(14, 'Rayos x de muñeca', 50.00, 6, 4, 'activo'),
(16, 'Rayos x de Codo', 20.00, 6, 4, 'activo'),
(18, 'Citologia', 20.00, 1, 6, 'inactivo'),
(19, 'Rayos x pie', 20.00, 6, 4, 'activo'),
(20, 'Eco razonal', 200.00, 6, 3, 'activo'),
(21, 'Consulta odontologica', 15.00, 3, 1, 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historias_medicas`
--

CREATE TABLE `historias_medicas` (
  `id_historia` int(11) NOT NULL,
  `ci_paciente` int(11) NOT NULL,
  `tipo_paciente` varchar(20) NOT NULL,
  `ci_medico` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `edad` int(3) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `motivo_consulta` text NOT NULL,
  `enfermedad_actual` text NOT NULL,
  `antecedentes_familiares` text NOT NULL,
  `antecedentes_personales` text NOT NULL,
  `info_adicional` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historias_medicas`
--

INSERT INTO `historias_medicas` (`id_historia`, `ci_paciente`, `tipo_paciente`, `ci_medico`, `fecha`, `fecha_nacimiento`, `edad`, `direccion`, `motivo_consulta`, `enfermedad_actual`, `antecedentes_familiares`, `antecedentes_personales`, `info_adicional`) VALUES
(12, 3771659, 'afiliado', 22657123, '2026-06-02', '0000-00-00', 41, 'asd', 'asd', 'asd', 'asd', 'asd', 'asd');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historias_medicas_gine`
--

CREATE TABLE `historias_medicas_gine` (
  `id_historia_g` int(11) NOT NULL,
  `ci_paciente` int(11) NOT NULL,
  `tipo_paciente` varchar(20) NOT NULL,
  `ci_medico` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `edad` int(3) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `motivo_consulta` text NOT NULL,
  `enfermedad_actual` text NOT NULL,
  `antecedentes_familiares` text NOT NULL,
  `antecedentes_personales` text NOT NULL,
  `gs` varchar(20) NOT NULL,
  `fuma` varchar(20) NOT NULL,
  `ant_gineco_obstetrico` varchar(100) NOT NULL,
  `c.m` varchar(50) NOT NULL,
  `prs` varchar(50) NOT NULL,
  `cs` varchar(50) NOT NULL,
  `mac` varchar(50) NOT NULL,
  `fuc` varchar(100) NOT NULL,
  `fum` varchar(50) NOT NULL,
  `gestaciones` varchar(50) NOT NULL,
  `rc` varchar(50) NOT NULL,
  `año` year(4) NOT NULL,
  `otros` varchar(50) NOT NULL,
  `ex.fisico.t.a` varchar(20) NOT NULL,
  `f.c` varchar(20) NOT NULL,
  `peso` varchar(20) NOT NULL,
  `talla` varchar(20) NOT NULL,
  `cabeza` varchar(20) NOT NULL,
  `o.r.l` varchar(20) NOT NULL,
  `c.v` varchar(20) NOT NULL,
  `tiroides` varchar(20) NOT NULL,
  `mamas` varchar(20) NOT NULL,
  `abdomen` varchar(20) NOT NULL,
  `ginecologico` varchar(100) NOT NULL,
  `ultrasonido` varchar(100) NOT NULL,
  `diagnostico` varchar(100) NOT NULL,
  `conducta` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicos`
--

CREATE TABLE `medicos` (
  `ci_medico` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `especialidad` int(11) NOT NULL,
  `telefono_personal` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `medicos`
--

INSERT INTO `medicos` (`ci_medico`, `id_usuario`, `especialidad`, `telefono_personal`) VALUES
(22657123, 34, 3, '6545645465');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_contrato`
--

CREATE TABLE `pagos_contrato` (
  `ID_pago` int(11) NOT NULL,
  `ID_contrato` int(11) NOT NULL,
  `monto_cuota` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `numero_cuota` int(11) DEFAULT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `tasa_bcv` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monto_bs` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tipo_pago` varchar(20) NOT NULL DEFAULT 'Cuota'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_contrato`
--

INSERT INTO `pagos_contrato` (`ID_pago`, `ID_contrato`, `monto_cuota`, `fecha_pago`, `numero_cuota`, `metodo_pago`, `tasa_bcv`, `monto_bs`, `tipo_pago`) VALUES
(1, 1, 54.00, '2026-11-17', NULL, 'Transferencia', 915.75, 49450.50, 'Pago Inicial'),
(2, 1, 126.00, '2026-11-17', 1, 'Transferencia', 915.75, 115384.50, 'Cuota');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_externos`
--

CREATE TABLE `pagos_externos` (
  `id_pago_ext` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `monto_base` decimal(10,2) NOT NULL,
  `monto_final` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'Efectivo',
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_externos`
--

INSERT INTO `pagos_externos` (`id_pago_ext`, `id_cita`, `monto_base`, `monto_final`, `metodo_pago`, `fecha_pago`) VALUES
(25, 69, 20.00, 17.00, 'Efectivo', '2026-05-28 20:49:24'),
(26, 71, 20.00, 18.00, 'Efectivo', '2026-11-12 20:05:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `id_persona` int(11) NOT NULL,
  `cedula` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `fechanacimiento` date DEFAULT NULL,
  `genero` enum('Masculino','Femenino') DEFAULT NULL,
  `estado_civil` varchar(40) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `ocupacion` varchar(50) NOT NULL,
  `estado` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`id_persona`, `cedula`, `nombre`, `apellido`, `fechanacimiento`, `genero`, `estado_civil`, `telefono`, `correo`, `ocupacion`, `estado`) VALUES
(1, 3771659, 'Ana ', 'Salazar', '1984-08-30', 'Femenino', NULL, '446466654', 'asdda@asdasdaa', 'ama de casa', ''),
(2, 10505944, 'Teresa', 'Pérez', '1974-03-21', 'Femenino', 'No aplica', '04120411874', 'tere_34_11_3@gmail.com', 'Nada', ''),
(3, 12345678, 'alana', 'perez', '2000-02-08', 'Femenino', NULL, '46564654', 'asdasd@asdasd', 'asdasd', ''),
(4, 22657594, 'Luis ', 'Mendoza', '2015-04-02', 'Masculino', 'No aplica', '2147483647', 'asdasd@asdasdadad', 'asdads', ''),
(5, 28296254, 'genesis', 'contreras', '2002-09-14', 'Femenino', NULL, '5545664', 'asdasd@asddas', 'comerciante', ''),
(6, 30270422, 'Gregory', 'Pérezz', '2002-08-31', 'Masculino', NULL, '04147175394', 'gps.3108@gmail.com', 'asdasd', ''),
(7, 30492270, 'luis', 'balbo', '1994-05-08', 'Masculino', NULL, '04147175394', 'gps.3109@gmail.com', 'futbolista', ''),
(8, 34234140, 'Sebastian', 'Pérez', '2011-10-23', 'Masculino', 'No aplica', '2147483647', 'asddas@asdasdsda', 'estudiante', ''),
(9, 90000001, 'arturo', 'vidal', '2002-06-11', 'Masculino', NULL, '04147175394', 'gps.31088@gmail.com', 'asdasd', ''),
(10, 12308793, 'Jose', 'Pérez', NULL, NULL, NULL, '546654', 'No aplica', 'No aplica', 'No aplica'),
(11, 30960255, 'diana', 'torrega', NULL, NULL, NULL, '04120411874', 'No aplica', 'No aplica', 'No aplica'),
(12, 30270491, 'grecio', 'salazar', NULL, NULL, NULL, '04147175394', 'No aplica', 'No aplica', 'No aplica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `ID_planes` int(11) NOT NULL,
  `nombre_plan` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `monto_cobertura` decimal(10,2) NOT NULL DEFAULT 0.00,
  `anio` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`ID_planes`, `nombre_plan`, `precio`, `monto_cobertura`, `anio`, `activo`, `descripcion`) VALUES
(6, 'Plan salud 2026', 180.00, 800.00, 2026, 1, ''),
(7, 'Plan Salud Premium 2027', 140.00, 800.00, 2027, 0, ''),
(9, 'Plan Salud 2027', 140.00, 800.00, 2027, 1, 'Plan anual de salud 2027');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_seguridad`
--

CREATE TABLE `preguntas_seguridad` (
  `ID` int(11) NOT NULL,
  `pregunta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preguntas_seguridad`
--

INSERT INTO `preguntas_seguridad` (`ID`, `pregunta`) VALUES
(1, '¿En qué ciudad naciste?'),
(2, '¿Cuál es el nombre de tu escuela primaria?'),
(3, '¿Cuál es tu color favorito?'),
(4, '¿Cuál es tu animal favorito?'),
(5, '¿Cuál es el nombre de tu padre o madre?');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas_seguridad`
--

CREATE TABLE `respuestas_seguridad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pregunta_seguridad_id` int(11) NOT NULL,
  `respuesta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `respuestas_seguridad`
--

INSERT INTO `respuestas_seguridad` (`id`, `usuario_id`, `pregunta_seguridad_id`, `respuesta`) VALUES
(33, 25, 1, 'merida'),
(34, 25, 3, 'azul'),
(35, 27, 1, 'merida'),
(36, 27, 3, 'morado'),
(41, 31, 1, 'caracas'),
(42, 31, 3, 'verde'),
(47, 34, 1, 'merida'),
(48, 34, 3, 'marron');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `ID` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`ID`, `Nombre`) VALUES
(1, 'administrador'),
(2, 'usuario'),
(3, 'medico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_externos`
--

CREATE TABLE `tipos_externos` (
  `id_tipos_ext` int(11) NOT NULL,
  `nombre_tipo` varchar(50) NOT NULL,
  `descuento` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_externos`
--

INSERT INTO `tipos_externos` (`id_tipos_ext`, `nombre_tipo`, `descuento`) VALUES
(1, 'Estudiante', 10.00),
(2, 'Profesor no Afiliado', 15.00),
(3, 'Externo General', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `role_id`) VALUES
(7, 'admin', '$2y$10$XBg1P/iKyVALsB1oMK8Bv.PhjgjaVakouIwz0wjAl0tV/My/ow6ba', 1),
(25, 'grego', '$2y$10$Mg0yHFSuZJn9IuosEniBqOzf.duGs9/I2YyLpfxEEma0HWtItKiZG', 1),
(27, 'usuario', '$2y$10$/1OPBwmDoMLB6KKeMXGxMejHfMviGhH7FkkBc7ams3niG/fmfY3TK', 2),
(31, 'secretaria', '$2y$10$6c9yiRlxmWalX4aP6vCAOORlE5MKmdLmPOMzdIh1vaLbsaPajkEJK', 2),
(34, 'odontologo', '$2y$10$28X4uDnKDqr8Ua.NOBpZG.vz./KuqYiOS26a6F4MCaz8mszcmeTWa', 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `afiliados`
--
ALTER TABLE `afiliados`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_afiliados_persona` (`id_persona`);

--
-- Indices de la tabla `beneficiarios`
--
ALTER TABLE `beneficiarios`
  ADD PRIMARY KEY (`id_beneficiario`),
  ADD UNIQUE KEY `uq_beneficiarios_persona` (`id_persona`),
  ADD KEY `dependencia` (`id_afiliado`);

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`idbitacora`),
  ADD KEY `usuario` (`usuario`);

--
-- Indices de la tabla `categorias_examenes`
--
ALTER TABLE `categorias_examenes`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `id_especialidad` (`id_especialidad`),
  ADD KEY `idx_citas_persona` (`id_persona`);

--
-- Indices de la tabla `citas_examenes`
--
ALTER TABLE `citas_examenes`
  ADD PRIMARY KEY (`id_cita_examen`),
  ADD KEY `id_cita` (`id_cita`),
  ADD KEY `id_examen` (`id_examen`);

--
-- Indices de la tabla `componentes_planes`
--
ALTER TABLE `componentes_planes`
  ADD PRIMARY KEY (`ID_componenteplan`),
  ADD KEY `ID_examen_componentes` (`ID_examen_componentes`),
  ADD KEY `componentes_planes_ibfk_1` (`ID_planes_componentes`),
  ADD KEY `fk_componente_categoria` (`id_categoria_componente`);

--
-- Indices de la tabla `consumo_plan`
--
ALTER TABLE `consumo_plan`
  ADD PRIMARY KEY (`ID_consumo`),
  ADD KEY `ID_contrato_plan` (`ID_contrato_plan`),
  ADD KEY `ID_examen_plan` (`ID_examen_plan`),
  ADD KEY `fk_consumo_cita` (`id_cita`),
  ADD KEY `idx_consumo_persona` (`id_persona`);

--
-- Indices de la tabla `contrato_cuotas`
--
ALTER TABLE `contrato_cuotas`
  ADD PRIMARY KEY (`id_cuota`),
  ADD UNIQUE KEY `uq_contrato_cuota` (`id_contrato`,`numero_cuota`),
  ADD KEY `idx_cuota_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_cuota_estado` (`estado`),
  ADD KEY `fk_cuota_pago` (`id_pago`);

--
-- Indices de la tabla `contrato_plan`
--
ALTER TABLE `contrato_plan`
  ADD PRIMARY KEY (`ID_contrato`),
  ADD KEY `ID_planes_contrato` (`ID_planes_contrato`),
  ADD KEY `idx_contrato_persona` (`id_persona`),
  ADD KEY `idx_contrato_anio` (`anio`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id_especialidad`);

--
-- Indices de la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`ID_examen`),
  ADD KEY `fk_examen_especialidad` (`ID_especialidad_examenes`),
  ADD KEY `fk_examen_categoria` (`id_categoria`);

--
-- Indices de la tabla `historias_medicas`
--
ALTER TABLE `historias_medicas`
  ADD PRIMARY KEY (`id_historia`),
  ADD KEY `ci_medico` (`ci_medico`);

--
-- Indices de la tabla `historias_medicas_gine`
--
ALTER TABLE `historias_medicas_gine`
  ADD PRIMARY KEY (`id_historia_g`),
  ADD KEY `ci_medico` (`ci_medico`);

--
-- Indices de la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`ci_medico`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `especialidad` (`especialidad`);

--
-- Indices de la tabla `pagos_contrato`
--
ALTER TABLE `pagos_contrato`
  ADD PRIMARY KEY (`ID_pago`),
  ADD KEY `ID_contrato` (`ID_contrato`);

--
-- Indices de la tabla `pagos_externos`
--
ALTER TABLE `pagos_externos`
  ADD PRIMARY KEY (`id_pago_ext`),
  ADD KEY `id_cita` (`id_cita`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`id_persona`),
  ADD UNIQUE KEY `uq_persona_cedula` (`cedula`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`ID_planes`),
  ADD KEY `idx_planes_anio_activo` (`anio`,`activo`);

--
-- Indices de la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `respuestas_seguridad`
--
ALTER TABLE `respuestas_seguridad`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_pregunta` (`usuario_id`,`pregunta_seguridad_id`),
  ADD KEY `pregunta_seguridad_id` (`pregunta_seguridad_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`ID`);

--
-- Indices de la tabla `tipos_externos`
--
ALTER TABLE `tipos_externos`
  ADD PRIMARY KEY (`id_tipos_ext`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `roles` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `afiliados`
--
ALTER TABLE `afiliados`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `beneficiarios`
--
ALTER TABLE `beneficiarios`
  MODIFY `id_beneficiario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `idbitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de la tabla `categorias_examenes`
--
ALTER TABLE `categorias_examenes`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `citas_examenes`
--
ALTER TABLE `citas_examenes`
  MODIFY `id_cita_examen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `componentes_planes`
--
ALTER TABLE `componentes_planes`
  MODIFY `ID_componenteplan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `consumo_plan`
--
ALTER TABLE `consumo_plan`
  MODIFY `ID_consumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `contrato_cuotas`
--
ALTER TABLE `contrato_cuotas`
  MODIFY `id_cuota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `contrato_plan`
--
ALTER TABLE `contrato_plan`
  MODIFY `ID_contrato` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id_especialidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `examenes`
--
ALTER TABLE `examenes`
  MODIFY `ID_examen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `historias_medicas`
--
ALTER TABLE `historias_medicas`
  MODIFY `id_historia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `historias_medicas_gine`
--
ALTER TABLE `historias_medicas_gine`
  MODIFY `id_historia_g` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `medicos`
--
ALTER TABLE `medicos`
  MODIFY `ci_medico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226571235;

--
-- AUTO_INCREMENT de la tabla `pagos_contrato`
--
ALTER TABLE `pagos_contrato`
  MODIFY `ID_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pagos_externos`
--
ALTER TABLE `pagos_externos`
  MODIFY `id_pago_ext` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `id_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `ID_planes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `preguntas_seguridad`
--
ALTER TABLE `preguntas_seguridad`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `respuestas_seguridad`
--
ALTER TABLE `respuestas_seguridad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_externos`
--
ALTER TABLE `tipos_externos`
  MODIFY `id_tipos_ext` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `afiliados`
--
ALTER TABLE `afiliados`
  ADD CONSTRAINT `fk_afiliados_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `beneficiarios`
--
ALTER TABLE `beneficiarios`
  ADD CONSTRAINT `dependencia` FOREIGN KEY (`id_afiliado`) REFERENCES `afiliados` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_beneficiarios_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `usuario` FOREIGN KEY (`usuario`) REFERENCES `usuarios` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidades` (`id_especialidad`),
  ADD CONSTRAINT `fk_citas_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `citas_examenes`
--
ALTER TABLE `citas_examenes`
  ADD CONSTRAINT `citas_examenes_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_examenes_ibfk_2` FOREIGN KEY (`id_examen`) REFERENCES `examenes` (`ID_examen`);

--
-- Filtros para la tabla `componentes_planes`
--
ALTER TABLE `componentes_planes`
  ADD CONSTRAINT `componentes_planes_ibfk_1` FOREIGN KEY (`ID_planes_componentes`) REFERENCES `planes` (`ID_planes`),
  ADD CONSTRAINT `componentes_planes_ibfk_2` FOREIGN KEY (`ID_examen_componentes`) REFERENCES `examenes` (`ID_examen`),
  ADD CONSTRAINT `fk_componente_categoria` FOREIGN KEY (`id_categoria_componente`) REFERENCES `categorias_examenes` (`id_categoria`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consumo_plan`
--
ALTER TABLE `consumo_plan`
  ADD CONSTRAINT `consumo_plan_ibfk_1` FOREIGN KEY (`ID_contrato_plan`) REFERENCES `contrato_plan` (`ID_contrato`),
  ADD CONSTRAINT `consumo_plan_ibfk_3` FOREIGN KEY (`ID_examen_plan`) REFERENCES `examenes` (`ID_examen`),
  ADD CONSTRAINT `fk_consumo_cita` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_consumo_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `contrato_cuotas`
--
ALTER TABLE `contrato_cuotas`
  ADD CONSTRAINT `fk_cuota_contrato` FOREIGN KEY (`id_contrato`) REFERENCES `contrato_plan` (`ID_contrato`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cuota_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos_contrato` (`ID_pago`) ON DELETE SET NULL;

--
-- Filtros para la tabla `contrato_plan`
--
ALTER TABLE `contrato_plan`
  ADD CONSTRAINT `contrato_plan_ibfk_1` FOREIGN KEY (`ID_planes_contrato`) REFERENCES `planes` (`ID_planes`),
  ADD CONSTRAINT `fk_contrato_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD CONSTRAINT `fk_examen_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_examenes` (`id_categoria`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_examen_especialidad` FOREIGN KEY (`ID_especialidad_examenes`) REFERENCES `especialidades` (`id_especialidad`);

--
-- Filtros para la tabla `historias_medicas`
--
ALTER TABLE `historias_medicas`
  ADD CONSTRAINT `historias_medicas_ibfk_1` FOREIGN KEY (`ci_medico`) REFERENCES `medicos` (`ci_medico`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `historias_medicas_gine`
--
ALTER TABLE `historias_medicas_gine`
  ADD CONSTRAINT `historias_medicas_gine_ibfk_1` FOREIGN KEY (`ci_medico`) REFERENCES `medicos` (`ci_medico`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `medicos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `medicos_ibfk_2` FOREIGN KEY (`especialidad`) REFERENCES `especialidades` (`id_especialidad`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos_contrato`
--
ALTER TABLE `pagos_contrato`
  ADD CONSTRAINT `pagos_contrato_ibfk_1` FOREIGN KEY (`ID_contrato`) REFERENCES `contrato_plan` (`ID_contrato`);

--
-- Filtros para la tabla `pagos_externos`
--
ALTER TABLE `pagos_externos`
  ADD CONSTRAINT `pagos_externos_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`);

--
-- Filtros para la tabla `respuestas_seguridad`
--
ALTER TABLE `respuestas_seguridad`
  ADD CONSTRAINT `respuestas_seguridad_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `respuestas_seguridad_ibfk_4` FOREIGN KEY (`pregunta_seguridad_id`) REFERENCES `preguntas_seguridad` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
