<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $title ?? config('store.name') }}</title>
<!--[if mso]>
<style type="text/css">
  table { border-collapse: collapse; }
  .fallback-font { font-family: Georgia, 'Times New Roman', serif !important; }
</style>
<![endif]-->
<style type="text/css">
  body, table, td { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  body { margin:0; padding:0; width:100% !important; background-color:#DCD5C4; }
  img { border:0; outline:none; text-decoration:none; display:block; }
  a { text-decoration:none; }

  @media only screen and (max-width:600px) {
    .email-container { width:100% !important; }
    .stack { display:block !important; width:100% !important; }
    .px-mobile { padding-left:20px !important; padding-right:20px !important; }
    .hero-title { font-size:26px !important; line-height:32px !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background-color:#DCD5C4;">

@isset($preheader)
<div style="display:none; max-height:0; overflow:hidden; font-size:1px; color:#DCD5C4; line-height:1px;">
  {{ $preheader }} &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
</div>
@endisset

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#DCD5C4;">
<tr>
<td align="center" style="padding: 32px 16px;">

<table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background-color:#E8E1D3;">

  {{-- Header --}}
  <tr>
    <td style="background-color:#2A1D16; padding:36px 40px 30px 40px;" align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" style="font-family: Georgia, 'Times New Roman', serif; font-size:13px; letter-spacing:4px; color:#A6874F; text-transform:uppercase; padding-bottom:10px;">
            {{ config('store.tagline') }}
          </td>
        </tr>
        <tr>
          <td align="center" style="font-family: Georgia, 'Times New Roman', serif; font-size:28px; letter-spacing:1px; color:#E8E1D3;">
            {{ strtoupper(config('store.name')) }}
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Brass stitch divider --}}
  <tr>
    <td style="background-color:#2A1D16; padding:0 40px 22px 40px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="font-size:0; line-height:0; border-top:1px dashed #A6874F; height:1px;">&nbsp;</td>
        </tr>
        <tr><td style="height:3px; line-height:3px; font-size:0;">&nbsp;</td></tr>
        <tr>
          <td style="font-size:0; line-height:0; border-top:1px dashed #A6874F; height:1px;">&nbsp;</td>
        </tr>
      </table>
    </td>
  </tr>

  @yield('banner')

  @yield('hero')

  @include('emails.partials.order-items', ['order' => $order])

  @include('emails.partials.order-summary', ['order' => $order])

  @hasSection('cta')
    @yield('cta')
  @elseif(!empty($ctaUrl) && !empty($ctaLabel))
    @include('emails.partials.cta-button', ['ctaUrl' => $ctaUrl, 'ctaLabel' => $ctaLabel])
  @endif

  @include('emails.partials.shipping-address', ['order' => $order])

  {{-- Footer --}}
  <tr>
    <td style="padding:44px 48px 40px 48px;" align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="font-size:0; line-height:0; border-top:1px dashed #C9BBA1; height:1px;">&nbsp;</td>
        </tr>
        <tr><td style="height:26px; line-height:26px; font-size:0;">&nbsp;</td></tr>
        <tr>
          <td align="center" style="font-family: Helvetica, Arial, sans-serif; font-size:12px; line-height:20px; color:#8A7D6C;">
            Questions about your order? Reply to this email or reach us at
            <a href="mailto:{{ config('store.care_email') }}" style="color:#5C1F22;">{{ config('store.care_email') }}</a>
          </td>
        </tr>
        <tr><td style="height:18px; line-height:18px; font-size:0;">&nbsp;</td></tr>
        <tr>
          <td align="center" style="font-family:'Courier New', Courier, monospace; font-size:10px; letter-spacing:1.5px; color:#B7A98C; text-transform:uppercase;">
            {{ config('store.name') }} &nbsp;·&nbsp; {{ config('store.location') }}
          </td>
        </tr>
        @if(config('store.preferences_url'))
        <tr><td style="height:10px; line-height:10px; font-size:0;">&nbsp;</td></tr>
        <tr>
          <td align="center" style="font-family: Helvetica, Arial, sans-serif; font-size:11px; color:#B7A98C;">
            <a href="{{ config('store.preferences_url') }}" style="color:#B7A98C; text-decoration:underline;">Manage email preferences</a>
          </td>
        </tr>
        @endif
      </table>
    </td>
  </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
