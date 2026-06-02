import { Icon } from '../components/ui/Icon'
import { CompactSslCard, MetricCard, UptimeChart } from '../features/dashboard/components/DashboardWidgets'
import { logs, websites } from '../features/dashboard/data'

function DashboardPage() {
  return (
    <>
      <section className="tk-overview">
        <div className="tk-metrics">
          <MetricCard label="Tổng số website" value="12" detail="↗ 2 website mới" icon="monitor" tone="blue" />
          <MetricCard label="Website hoạt động" value="11" detail="↗ 91.7% uptime trung bình" icon="check" tone="green" />
          <MetricCard label="Lỗi đang xảy ra" value="3" detail="↘ -2 lỗi" icon="alert" tone="yellow" />
        </div>
        <div className="tk-overview-side">
          <div className="tk-date-filter"><span>7 ngày qua (16/05 - 22/05/2024)</span><Icon name="chevron" size={14} /><button aria-label="Làm mới"><Icon name="refresh" size={15} /></button></div>
          <CompactSslCard />
        </div>
      </section>

      <section className="tk-grid-main">
        <article className="tk-card tk-panel tk-uptime">
          <div className="tk-panel-head"><h2>Uptime 30 ngày qua</h2><button>Tất cả website⌄</button></div>
          <UptimeChart />
        </article>
        <article className="tk-card tk-panel tk-websites">
          <div className="tk-panel-head"><h2>Website đang giám sát</h2><button>Xem tất cả</button></div>
          <table>
            <thead><tr><th>Website</th><th>Trạng thái</th><th>Uptime</th><th>SSL</th><th>WordPress</th><th>Lần check cuối</th></tr></thead>
            <tbody>
              {websites.map((site) => (
                <tr key={site.domain}>
                  <td><i className={`tk-site-icon ${site.tone}`}>W</i><b>{site.domain}</b><small>{site.url}</small></td>
                  <td><em className={site.tone}>● {site.tone === 'red' ? 'Lỗi' : 'Hoạt động'}</em></td>
                  <td>{site.uptime}</td><td><em className={site.ssl === 'Hết hạn' ? 'red' : 'green'}><Icon name="lock" size={11} /> {site.ssl}</em></td>
                  <td>{site.version}</td><td>{site.checked} <em className={site.tone}>●</em></td>
                </tr>
              ))}
            </tbody>
          </table>
        </article>
      </section>

      <section className="tk-grid-bottom">
        <article className="tk-card tk-panel">
          <div className="tk-panel-head"><h2>SSL sắp hết hạn <b className="tk-count">2</b></h2><button>Xem tất cả</button></div>
          <div className="tk-ssl-row"><i className="tk-site-icon blue">W</i><span><b>demo.tnstack.net</b><small>https://demo.tnstack.net</small></span><strong>Còn <em>12 ngày</em></strong></div>
          <div className="tk-progress"><i /></div>
          <div className="tk-ssl-row"><i className="tk-site-icon red">W</i><span><b>blog.tnstack.net</b><small>https://blog.tnstack.net</small></span><strong>Còn <em className="red">3 ngày</em></strong></div>
          <div className="tk-progress red"><i /></div>
        </article>
        <article className="tk-card tk-panel tk-malware">
          <div className="tk-panel-head"><h2>Malware Scan</h2><button>Quét ngay</button></div>
          <div className="tk-malware-main"><div className="tk-shield"><Icon name="shield" size={42} /></div><span><b>Không phát hiện mối đe dọa</b><small>Lần quét gần nhất: 22/05/2024 - 10:30 AM</small><small>Tất cả website đều an toàn.</small></span></div>
          <div className="tk-malware-stats"><span><b className="green">12</b><small>Đã quét</small></span><span><b>0</b><small>Mối đe dọa</small></span><span><b>0</b><small>File đáng ngờ</small></span><span><b className="green">12</b><small>An toàn</small></span></div>
        </article>
        <article className="tk-card tk-panel">
          <div className="tk-panel-head"><h2>Log lỗi gần đây</h2><button>Xem tất cả</button></div>
          <div className="tk-logs">
            {logs.map((log) => <div key={`${log.type}-${log.time}`}><b className={log.tone}>{log.type}</b><span>{log.text}</span><small>{log.site}</small><time>{log.time}</time></div>)}
          </div>
        </article>
      </section>

      <footer className="tk-system-footer">
        <span>System Status: <b className="green">● Hoạt động</b></span>
        <span>PHP 8.1.20</span>
        <span>MySQL 8.0.33</span>
        <span>WP 6.5.2</span>
        <span>Memory: 128M / 256M (50%)</span>
        <small>© 2026 TNStack. All rights reserved.</small>
      </footer>
    </>
  )
}

export default DashboardPage
