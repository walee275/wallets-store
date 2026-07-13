@php
    $address = $order->shipping_address_json ?? [];
    $lines = collect([
        $address['name'] ?? null,
        collect([$address['line1'] ?? null, $address['line2'] ?? null])->filter()->implode(', '),
        collect([
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ])->filter()->implode(', '),
    ])->filter()->values();
@endphp

@if($lines->isNotEmpty())
<tr>
  <td class="px-mobile" style="padding:40px 48px 0 48px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #C9BBA1;">
      <tr>
        <td style="padding:20px 24px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="font-family:'Courier New', Courier, monospace; font-size:11px; letter-spacing:2px; color:#8A7D6C; text-transform:uppercase; padding-bottom:10px;">
                Shipping To
              </td>
            </tr>
            <tr>
              <td style="font-family: Helvetica, Arial, sans-serif; font-size:14px; line-height:22px; color:#1C1815;">
                {!! $lines->map(fn ($line) => e($line))->implode('<br>') !!}
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endif
