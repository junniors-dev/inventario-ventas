@extends('errors.layout')

@section('codigo', '403')
@section('titulo', 'No tienes permiso')
@section('mensaje', $exception?->getMessage() ?: 'Esta sección está reservada a los administradores del sistema.')
