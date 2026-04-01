<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    private function db()
    {
        return DB::connection('ziyaretci');
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'dashboard');

        // --- Özet sayaçlar (her zaman yükle) ---
        try {
            $todayTotal  = (int) $this->db()->selectOne("SELECT COUNT(*) c FROM gt_visits WHERE DATE(created_at)=CURDATE()")->c;
            $today404    = (int) $this->db()->selectOne("SELECT COUNT(*) c FROM gt_visits WHERE is_404=1 AND DATE(created_at)=CURDATE()")->c;
            $today403    = (int) $this->db()->selectOne("SELECT COUNT(*) c FROM gt_visits WHERE is_403=1 AND DATE(created_at)=CURDATE()")->c;
            $today500    = (int) $this->db()->selectOne("SELECT COUNT(*) c FROM gt_visits WHERE is_500=1 AND DATE(created_at)=CURDATE()")->c;
            $riskyIps    = (int) $this->db()->selectOne("SELECT COUNT(DISTINCT ip) c FROM gt_visits WHERE risk_score>=50 AND DATE(created_at)=CURDATE()")->c;
            $loginFail   = (int) $this->db()->selectOne("SELECT COUNT(*) c FROM gt_events WHERE event_type='login_failed' AND DATE(created_at)=CURDATE()")->c;
            $onlineCount = (int) $this->db()->selectOne("SELECT COUNT(*) c FROM gt_online WHERE last_seen > NOW() - INTERVAL 5 MINUTE")->c;

            // Saatlik trafik
            $hourlyRows = $this->db()->select("SELECT HOUR(created_at) h, COUNT(*) c FROM gt_visits WHERE created_at >= NOW() - INTERVAL 24 HOUR GROUP BY HOUR(created_at)");
            $hourly = array_fill(0, 24, 0);
            foreach ($hourlyRows as $r) {
                $hourly[(int)$r->h] = (int)$r->c;
            }

            $topIps = $this->db()->select("SELECT ip, MAX(risk_score) max_risk, COUNT(*) hits, MAX(created_at) last_seen FROM gt_visits GROUP BY ip ORDER BY max_risk DESC, hits DESC LIMIT 10");
            $topPages = $this->db()->select("SELECT page_url, COUNT(*) c, COUNT(DISTINCT ip) u FROM gt_visits WHERE created_at >= NOW() - INTERVAL 7 DAY GROUP BY page_url ORDER BY c DESC LIMIT 10");
            $topCountries = $this->db()->select("SELECT flag, country, COUNT(DISTINCT ip) u FROM gt_visits WHERE country <> '' GROUP BY country, flag ORDER BY u DESC LIMIT 8");
        } catch (\Throwable $e) {
            $todayTotal = $today404 = $today403 = $today500 = $riskyIps = $loginFail = $onlineCount = 0;
            $hourly = array_fill(0, 24, 0);
            $topIps = $topPages = $topCountries = [];
            $dbError = $e->getMessage();
        }

        // --- Tab: IP Timeline ---
        $timelineIp = trim($request->get('ip', ''));
        $timelineRows = $timelineInfo = $timelineStats = null;

        if ($tab === 'timeline' && $timelineIp !== '') {
            try {
                $timelineRows  = $this->db()->select("SELECT * FROM gt_visits WHERE ip = ? ORDER BY created_at DESC LIMIT 200", [$timelineIp]);
                $timelineInfo  = $this->db()->selectOne("SELECT ip, MAX(flag) flag, MAX(country) country, MAX(city) city, MAX(isp) isp, MAX(device) device, MAX(browser) browser, MAX(os) os FROM gt_visits WHERE ip = ? GROUP BY ip LIMIT 1", [$timelineIp]);
                $timelineStats = $this->db()->selectOne("SELECT COUNT(*) total, SUM(is_404) c404, SUM(is_403) c403, SUM(is_500) c500, MAX(risk_score) max_risk, AVG(risk_score) avg_risk, MAX(created_at) last_seen FROM gt_visits WHERE ip = ?", [$timelineIp]);
            } catch (\Throwable $e) {}
        }

        // --- Tab: 404 Raporu ---
        $notFoundRows = [];
        if ($tab === '404') {
            try {
                $notFoundRows = $this->db()->select("SELECT page_url, COUNT(*) c, COUNT(DISTINCT ip) u, MAX(created_at) last_seen FROM gt_visits WHERE is_404=1 GROUP BY page_url ORDER BY c DESC LIMIT 50");
            } catch (\Throwable $e) {}
        }

        // --- Tab: Üye Aktivitesi ---
        $memberRows  = [];
        $memberIp    = trim($request->get('member_ip', ''));
        $memberName  = trim($request->get('member_name', ''));
        if ($tab === 'uyeler') {
            try {
                $memberRows = $this->db()->select("
                    SELECT member_id, member_name,
                           COUNT(*) total_hits,
                           COUNT(DISTINCT DATE(created_at)) aktif_gun,
                           MAX(created_at) last_seen,
                           MAX(ip) last_ip,
                           MAX(country) country,
                           MAX(flag) flag
                    FROM gt_visits
                    WHERE member_id <> '' AND member_id IS NOT NULL
                    GROUP BY member_id, member_name
                    ORDER BY last_seen DESC
                    LIMIT 100
                ");
            } catch (\Throwable $e) {}
        }

        // --- Tab: Üye Detay ---
        $memberDetail = [];
        $memberDetailName = trim($request->get('uye', ''));
        if ($tab === 'uye-detay' && $memberDetailName !== '') {
            try {
                $memberDetail = $this->db()->select("
                    SELECT page_url, created_at, ip, country, flag, device, browser, os, http_status
                    FROM gt_visits
                    WHERE member_id = ?
                    ORDER BY created_at DESC
                    LIMIT 300
                ", [$memberDetailName]);
            } catch (\Throwable $e) {}
        }

        return view('superadmin.istatistik', compact(
            'tab',
            'todayTotal', 'today404', 'today403', 'today500', 'riskyIps', 'loginFail', 'onlineCount',
            'hourly', 'topIps', 'topPages', 'topCountries',
            'timelineIp', 'timelineRows', 'timelineInfo', 'timelineStats',
            'notFoundRows',
            'memberRows', 'memberDetailName', 'memberDetail'
        ))->with('dbError', $dbError ?? null);
    }
}
