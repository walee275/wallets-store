@php
    use App\Support\Money;
@endphp

{{-- Thin stitch divider --}}
<tr>
  <td class="px-mobile" style="padding:6px 48px 0 48px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr><td style="font-size:0; line-height:0; border-top:1px dashed #C9BBA1; height:1px;">&nbsp;</td></tr>
    </table>
  </td>
</tr>

{{-- Order summary --}}
<tr>
  <td class="px-mobile" style="padding:20px 48px 0 48px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:'Courier New', Courier, monospace; font-size:13px; color:#4A4038;">
      <tr>
        <td style="padding-bottom:8px;">Subtotal</td>
        <td align="right" style="padding-bottom:8px;">{{ Money::formatEmail($order->subtotal_cents, $order->currency) }}</td>
      </tr>
      <tr>
        <td style="padding-bottom:8px;">Shipping</td>
        <td align="right" style="padding-bottom:8px;">{{ Money::formatEmail($order->shipping_cents, $order->currency) }}</td>
      </tr>
      @if($order->discount_cents > 0)
      <tr>
        <td style="padding-bottom:14px;">Discount @if($order->discount_code)&nbsp;({{ $order->discount_code }})@endif</td>
        <td align="right" style="padding-bottom:14px; color:#5C1F22;">&minus;{{ Money::formatEmail($order->discount_cents, $order->currency) }}</td>
      </tr>
      @endif
      @if($order->tax_cents > 0)
      <tr>
        <td style="padding-bottom:8px;">Tax</td>
        <td align="right" style="padding-bottom:8px;">{{ Money::formatEmail($order->tax_cents, $order->currency) }}</td>
      </tr>
      @endif
      <tr>
        <td style="border-top:1px solid #1C1815; padding-top:12px; font-size:15px; color:#1C1815; font-weight:bold;">Total</td>
        <td align="right" style="border-top:1px solid #1C1815; padding-top:12px; font-size:15px; color:#1C1815; font-weight:bold;">{{ Money::formatEmail($order->total_cents, $order->currency) }}</td>
      </tr>
    </table>
  </td>
</tr>
