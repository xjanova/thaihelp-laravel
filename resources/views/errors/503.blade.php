@extends('errors.layout')

@section('code', '503')
@section('title', 'กำลังปรับปรุงระบบ')
@section('bubble', 'รอแป๊บนะคะ!')
@section('glow-color', 'rgba(249, 115, 22, 0.15)')
@section('border-color', 'rgba(249, 115, 22, 0.4)')
@section('accent-color', '#f97316')
@section('accent-color-light', '#fb923c')

@section('message')
    น้องหญิงกำลังอัปเกรดระบบให้ดีขึ้นค่ะ<br>
    กรุณารอสักครู่แล้วลองใหม่อีกครั้งนะคะ
@endsection

@section('actions')
    <button class="btn btn-primary" onclick="location.reload()">
        &#x21bb; ลองใหม่
    </button>
@endsection
