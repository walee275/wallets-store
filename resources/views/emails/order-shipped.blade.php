@extends('emails.layouts.order')

@section('banner')
<tr>
  <td style="background-color:#8C5A2B; padding:16px 40px;" align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center" style="font-family: 'Courier New', Courier, monospace; font-size:12px; letter-spacing:2px; color:#F5EFE3; text-transform:uppercase;">
          Order Shipped &nbsp;·&nbsp; Tracking: {{ $order->tracking_number ?: $order->number }}
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
          It's on its way.
        </td>
      </tr>
      <tr>
        <td align="center" style="padding-top:14px; font-family: Helvetica, Arial, sans-serif; font-size:15px; line-height:24px; color:#4A4038;">
          {{ $customerFirstName }}, your order has left the atelier
          @if($order->carrier)
            with {{ $order->carrier }}
          @endif
          @if($order->tracking_number)
            <br class="stack">Tracking number:
            <span style="font-family:'Courier New', Courier, monospace; color:#1C1815;">{{ $order->tracking_number }}</span>
          @endif
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
