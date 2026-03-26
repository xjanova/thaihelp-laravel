@extends('errors.layout')

@section('code', '401')
@section('title', 'กรุณาเข้าสู่ระบบ')
@section('bubble', 'ล็อกอินก่อนนะคะ!')
@section('glow-color', 'rgba(234, 179, 8, 0.15)')
@section('border-color', 'rgba(234, 179, 8, 0.4)')
@section('accent-color', '#eab308')
@section('accent-color-light', '#facc15')

@section('message')
    ต้องเข้าสู่ระบบก่อนถึงจะใช้งานหน้านี้ได้ค่ะ<br>
    สมัครฟรี! ได้คะแนนจากการรายงานด้วยนะคะ
@endsection

@section('actions')
    <a href="/login" class="btn btn-primary">&#x1f511; เข้าสู่ระบบ</a>
    <a href="/" class="btn btn-secondary">&#x1f5fa; กลับแผนที่</a>
@endsection
