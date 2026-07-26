{{-- Template thermal (bawaan = sama default); edit terpisah di Admin → Template Editor. --}}
@php
    $validity = $validity ?? '';
    $timelimit = $timelimit ?? '';
    $getsprice = (string) ($getsprice ?? '');

    if (is_string($validity) && strlen($validity) > 0) {
        if (substr($validity, -1) == 'd') {
            $validity = '   <br>MASA AKTIF : '.substr($validity, 0, -1).' Hari';
        } elseif (substr($validity, -1) == 'h') {
            $validity = 'MASA AKTIF : '.substr($validity, 0, -1).'Jam';
        }
    }
    if (is_string($timelimit) && strlen($timelimit) > 0) {
        if (substr($timelimit, -1) == 'd' & strlen($timelimit) > 3) {
            $timelimit = 'Durasi:'.((substr($timelimit, 0, -1) * 7) + substr($timelimit, 2, 1)).' HARI';
        } elseif (substr($timelimit, -1) == 'd') {
            $timelimit = 'Durasi:'.substr($timelimit, 0, -1).' HARI';
        } elseif (substr($timelimit, -1) == 'h') {
            $timelimit = 'Durasi:'.substr($timelimit, 0, -1).'Jam';
        } elseif (substr($timelimit, -1) == 'w') {
            $timelimit = 'Durasi:'.(substr($timelimit, 0, -1) * 7).' HARI';
        }
    }

    if ($getsprice == '1000') {
        $color = '#666';
    } elseif ($getsprice == '2000') {
        $color = '#00F31A';
    } elseif ($getsprice == '3000') {
        $color = '#0438E8';
    } elseif ($getsprice == '4000') {
        $color = '#FA7207';
    } elseif ($getsprice == '5000') {
        $color = '#FF4500';
    } elseif ($getsprice == '10000') {
        $color = '#E65100';
    } elseif ($getsprice == '15000') {
        $color = '#228B22';
    } elseif ($getsprice == '20000') {
        $color = '#008000';
    } elseif ($getsprice == '30000') {
        $color = '#FF00FF';
    } elseif ($getsprice == '60000') {
        $color = '#E60C00';
    } elseif ($getsprice == '70000') {
        $color = '#FF0000';
    } else {
        $color = '#BA68C8';
    }

    $price = $price ?? '';
    $priceParts = explode(' ', $price, 2);
    $priceCurrency = $priceParts[0] ?? '';
    $priceAmount = $priceParts[1] ?? '';
@endphp
<style>
.qrcode{
		height:45px;
		width:45px;
}
</style>
<table style="display: inline-block;border-collapse: collapse;border: 1px solid #666;margin: 2.5px;width: 190px;overflow:hidden;position:relative;padding: 0px;margin: 2px;border: 1px solid #000000;">
<tbody>
	<tr>
		<td style="color:#666;" valign="top">
			<table style="width:100%;">
<tbody>
<tr>
<td style="width:75px">
<div style="position:relative;z-index:-1;padding: 0px;float:left;">
<div style="position:absolute;top:0;display:inline;margin-top:-100px;width: 0; height: 0; border-top: 230px solid transparent;border-left: 50px solid transparent;border-right:140px solid #DCDCDC; "></div>
</div>
<img style="margin:0px 30% 0 0px;" width="80" height="20" src="{{ $logo_session ?? $logo_default ?? $logo }}" alt="logo">
<span id="num" style="margin:0px 40% 0 0px; font-size:10px; line-height: 2px;"> [{{ $num }}]</span>
</td>
<td style="width:115px">
<div style="float:right;margin-top:-6px;margin-right:0px;width:5%;text-align:right;font-size:7px;">
</div>
<div style="text-align:right;font-weight:bold;font-family:Tahoma;font-size:20px;padding-left:17px;color:{{ $color }}">
<small style="font-size:10px;margin-left:-17px;position:absolute;">{{ $priceCurrency }}</small>{{ $priceAmount }}
</div>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
<tr>
	<td style="color:#666;border-collapse: collapse;" valign="top">
		<table style="width:100%;border-collapse: collapse;">
			<tbody>
				<tr>
					<td style="width:95px" valign="top" >
						<div style="clear:both;color:#555;margin-top:2px;margin-bottom:2.5px;">
@if(($v_opsi ?? '') == 'up')
@else
							<div style="padding:0px;border-bottom:1px solid {{ $color }};text-align:center;font-weight:bold;font-size:8px;">VOUCHER</div>
							<div style="padding:0px;border-bottom:1px solid {{ $color }};text-align:center;font-weight:bold;font-size:12px;;color:#000000;">{{ $username }}</div>
@endif
						</div>
<div style="text-align:justify;color:#111;font-size:6px;font-weight:bold;margin:0px;padding:2.5px;">
Hubungkan ke WiFi Voucher
Buka browser ketik : {{ $dnsname ?? 'hotspot' }} atau scan kode disamping setelah terhubung jaringan
</div>
</td>
<td style="width:100px;text-align:right;">
<div style="clear:both;padding:0 2.5px;font-size:7px;font-weight:bold;color:#000000">
{!! $validity !!}<br> {!! $timelimit !!} <br>{{ $datalimit }}
</div>
<div style="float:right;padding:1px;text-align:right;width:70%;margin:0 5px -20px 0;">{!! $qrcode !!}</div>
</td>
</tr>
<tr>
<td style="background:{{ $color }};color:#666;padding:0px;" valign="top" colspan="2">
								<div style="text-align:left;color:#fff;font-size:9px;font-weight:bold;margin:0px;padding:2.5px;">
								CS: <b>085707004054 (Andi)</b>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
