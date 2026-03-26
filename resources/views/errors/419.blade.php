@extends('errors.layout')

@section('code', '419')
@section('title', 'หน้าเว็บหมดอายุ')
@section('bubble', 'รีเฟรชนะคะ!')
@section('glow-color', 'rgba(168, 85, 247, 0.15)')
@section('border-color', 'rgba(168, 85, 247, 0.4)')
@section('accent-color', '#a855f7')
@section('accent-color-light', '#c084fc')

@section('message')
    เซสชันหมดอายุแล้วค่ะ เกิดจากเปิดหน้าเว็บไว้นานเกินไป<br>
    กดรีเฟรชแล้วลองใหม่อีกครั้งนะคะ
@endsection

@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">&#x21bb; รีเฟรช</button>
    <a href="/" class="btn btn-secondary">&#x1f5fa; กลับแผนที่</a>
@endsection
