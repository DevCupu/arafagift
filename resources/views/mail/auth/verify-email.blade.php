@extends('mail.layout')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#082016;">Verifikasi email Anda</h1>
    <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#475569;">
        Halo {{ $user->name }}, klik tombol di bawah untuk memverifikasi alamat email dan mengaktifkan akun Anda.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 8px;">
        <tr>
            <td style="border-radius:8px;background:#082016;">
                <a href="{{ $verifyUrl }}" style="display:inline-block;padding:12px 22px;font-size:13px;font-weight:600;color:#FFFFFF;text-decoration:none;">
                    Verifikasi email
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:12px;line-height:1.7;color:#6B7A70;">
        Tautan berlaku 60 menit. Kalau Anda tidak merasa mendaftar, abaikan email ini.
    </p>
@endsection
