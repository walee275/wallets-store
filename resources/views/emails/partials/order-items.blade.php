@php
    use App\Support\Money;
@endphp

{{-- Product line items --}}
<tr>
  <td class="px-mobile" style="padding:36px 48px 0 48px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      @foreach($order->items as $item)
        @php
          $imageUrl = $item->emailThumbnailUrl();
          $altText = $item->emailAltText();
        @endphp
        <tr>
          <td width="88" valign="top" style="padding-bottom:22px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
              <tr>
                <td style="width:76px; height:76px; background-color:#2A1D16; padding:2px;">
                  @if($imageUrl)
                    <!--[if mso]>
                    <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:72px;height:72px;">
                    <v:fill type="frame" src="{{ $imageUrl }}" color="#2A1D16" />
                    <v:textbox inset="0,0,0,0"><![endif]-->
                    <img src="{{ $imageUrl }}"
                         width="72" height="72" alt="{{ $altText }}"
                         style="width:72px; height:72px; display:block; object-fit:cover; border:0; outline:none; text-decoration:none;">
                    <!--[if mso]></v:textbox></v:rect><![endif]-->
                  @else
                    {{-- Espresso placeholder when no image is available --}}
                    <div style="width:72px; height:72px; background-color:#2A1D16; font-family:'Courier New', Courier, monospace; font-size:9px; line-height:72px; text-align:center; color:#A6874F;" role="img" aria-label="{{ $altText }}">
                      &nbsp;
                    </div>
                  @endif
                </td>
              </tr>
            </table>
          </td>
          <td valign="top" style="padding-left:18px; padding-bottom:22px; font-family: Helvetica, Arial, sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr><td style="font-size:15px; color:#1C1815; font-weight:bold; padding-bottom:4px;">{{ $item->product_title }}</td></tr>
              <tr><td style="font-family:'Courier New', Courier, monospace; font-size:12px; color:#8A7D6C; letter-spacing:0.5px;">{{ $item->emailVariantMeta() }}</td></tr>
            </table>
          </td>
          <td valign="top" align="right" style="padding-bottom:22px; font-family:'Courier New', Courier, monospace; font-size:14px; color:#1C1815; white-space:nowrap;">
            {{ Money::formatEmail($item->total_cents, $order->currency) }}
          </td>
        </tr>
      @endforeach
    </table>
  </td>
</tr>
