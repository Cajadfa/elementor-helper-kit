<?php
/**
 * Customer-facing WooCommerce order dates and numeral presentation.
 * Does NOT modify WC_Order stored timestamps, checkout currency, customer data,
 * status or API fields. Server localization avoids stale JS/AJAX text.
 */
defined( 'ABSPATH' ) || exit;

/** Format a WC_DateTime in WordPress site's local timezone and Jalali calendar. */
function ehk_order_jalali_date( $date, $include_time = false ) {
	if ( $date instanceof DateTimeInterface ) {
		$timestamp = $date->getTimestamp();
	} elseif ( is_int( $date ) && $date > 0 ) {
		$timestamp = $date;
	} else {
		return '';
	}
	$timezone = wp_timezone();
	$gy = (int) wp_date( 'Y', $timestamp, $timezone );
	$gm = (int) wp_date( 'n', $timestamp, $timezone );
	$gd = (int) wp_date( 'j', $timestamp, $timezone );
	list( $jy, $jm, $jd ) = ehk_gregorian_to_jalali( $gy, $gm, $gd );
	$months = array(
		1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
		'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
	);
	if ( ! isset( $months[ $jm ] ) ) {
		return '';
	}
	$formatted = ehk_fa_digits( $jd ) . ' ' . $months[ $jm ] . ' ' . ehk_fa_digits( $jy );
	if ( $include_time ) {
		$formatted .= '، ساعت ' . ehk_fa_digits( wp_date( 'H:i', $timestamp, $timezone ) );
	}
	return $formatted;
}

/**
 * Translate text-node digits in WC-formatted HTML without rewriting HTML
 * attributes, links, product-image URLs, CSS classes or email addresses in href.
 * Call wp_kses_post() on the output at the presentation site.
 */
function ehk_order_fa_markup( $html ) {
	$chunks = preg_split( '/(<[^>]*>)/u', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $chunks ) ) {
		return (string) $html;
	}
	foreach ( $chunks as $i => $part ) {
		if ( 0 !== $i % 2 ) {
			continue; // HTML tag: never modify attributes or identifiers.
		}
		// Numeric entities from wc_price() (e.g. &#x6F8; or &#1776;)
		// contain ASCII digits that MUST remain ASCII. Turning them into
		// Persian digits creates invalid entities displayed literally.
		// Split text by complete character references, including &nbsp;,
		// and only translate ordinary visible text between them.
		$pieces = preg_split(
			'/(&\#(?:[xX][0-9A-Fa-f]+|[0-9]+);|&[A-Za-z][A-Za-z0-9]+;)/u',
			$part,
			-1,
			PREG_SPLIT_DELIM_CAPTURE
		);
		if ( ! is_array( $pieces ) ) {
			continue;
		}
		foreach ( $pieces as $index => $piece ) {
			if ( 0 === $index % 2 ) {
				$pieces[ $index ] = ehk_fa_digits( $piece );
			}
		}
		$chunks[ $i ] = implode( '', $pieces );
	}
	return implode( '', $chunks );
}


function ehk_gregorian_to_jalali( $gy, $gm, $gd ) {
	$gy = (int) $gy;
	$gm = (int) $gm;
	$gd = (int) $gd;
	$g_month_days = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
	$leap_year = ( $gm > 2 ) ? $gy + 1 : $gy;
	$days = 355666 + 365 * $gy + (int) floor( ( $leap_year + 3 ) / 4 )
		- (int) floor( ( $leap_year + 99 ) / 100 ) + (int) floor( ( $leap_year + 399 ) / 400 ) + $gd;
	for ( $i = 0; $i < $gm - 1; $i++ ) {
		$days += $g_month_days[ $i ];
	}
	$jy = -1595 + 33 * (int) floor( $days / 12053 );
	$days %= 12053;
	$jy += 4 * (int) floor( $days / 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		$jy += (int) floor( ( $days - 1 ) / 365 );
		$days = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + (int) floor( $days / 31 );
		$jd = 1 + $days % 31;
	} else {
		$jm = 7 + (int) floor( ( $days - 186 ) / 30 );
		$jd = 1 + ( $days - 186 ) % 30;
	}
	return array( $jy, $jm, $jd );
}

function ehk_fa_digits( $number ) {
	return strtr( (string) $number, array( '0'=>'۰', '1'=>'۱', '2'=>'۲', '3'=>'۳', '4'=>'۴', '5'=>'۵', '6'=>'۶', '7'=>'۷', '8'=>'۸', '9'=>'۹' ) );
}

