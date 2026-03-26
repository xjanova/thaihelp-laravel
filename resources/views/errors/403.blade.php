@extends('errors.layout')

@section('code', '403')
@section('title', 'ไม่มีสิทธิ์เข้าถึง')
@section('bubble', 'เข้าไม่ได้ค่ะ!')
@section('glow-color', 'rgba(239, 68, 68, 0.15)')
@section('border-color', 'rgba(239, 68, 68, 0.4)')
@section('accent-color', '#ef4444')
@section('accent-color-light', '#f87171')

@section('message')
    คุณไม่มีสิทธิ์เข้าถึงหน้านี้ค่ะ<br>
    ลองเข้าสู่ระบบหรือกลับไปหน้าหลักนะคะ
@endsection

@section('actions')
    <a href="/" class="btn btn-primary">&#x1f3e0; กลับหน้าหลัก</a>
    <a href="/login" class="btn btn-secondary">&#x1f511; เข้าสู่ระบบ</a>
@endsection
