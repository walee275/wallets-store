@extends('emails.layouts.order')

@section('banner')
<tr>
  <td style="background-color:#2A1D16; padding:16px 40px;" align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" style="font-family: 'Courier New', Courier, monospace; font-size:12px; letter-spacing:2px; color:#A6874F; text-transform:uppercase;">
          Delivered &nbsp;·&nbsp; We hope you love it
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection

@section('hero')
<tr>
  <td class="px-mobile" style="padding:44px 48px 8px 48px;" align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" class="hero-title" style="font-family: Georgia, 'Times New Roman', serif; font-size:30px; line-height:38px; color:#1C1815;">
          Delivered with care.
        </td>
      </tr>
      <tr>
        <td align="center" style="padding-top:14px; font-family: Helvetica, Arial, sans-serif; font-size:15px; line-height:24px; color:#4A4038;">
          {{ $customerFirstName }}, order {{ $order->number }} has arrived.<br class="stack">
          We hope the leather feels as good in hand as it did on the bench.
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
