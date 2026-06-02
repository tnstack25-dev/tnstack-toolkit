import { Icon, type IconName } from '../../../components/ui/Icon'

export function MetricCard({ label, value, detail, icon, tone }: { label: string; value: string; detail: string; icon: IconName; tone: string }) {
  return (
    <article className="tk-card tk-metric">
      <div>
        <p className={`tk-accent ${tone}`}>{label}</p>
        <strong>{value}</strong>
        <small>{detail}</small>
        <span>so với 7 ngày trước</span>
      </div>
      <div className={`tk-metric-icon ${tone}`}><Icon name={icon} size={22} /></div>
    </article>
  )
}

export function CompactSslCard() {
  return (
    <article className="tk-card tk-compact-ssl">
      <div>
        <p className="tk-accent purple">Sắp hết hạn SSL</p>
        <strong>2</strong>
        <span>Cần gia hạn trong 30 ngày tới</span>
      </div>
      <div className="tk-metric-icon purple"><Icon name="lock" size={20} /></div>
    </article>
  )
}

export function UptimeChart() {
  return (
    <div className="tk-chart-wrap">
      <svg className="tk-chart" viewBox="0 0 760 230" preserveAspectRatio="none" aria-label="Biểu đồ uptime 30 ngày">
        <defs>
          <linearGradient id="uptime-fill" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stopColor="#2186ff" stopOpacity=".42" />
            <stop offset="100%" stopColor="#2186ff" stopOpacity="0" />
          </linearGradient>
        </defs>
        {[20, 65, 110, 155, 200].map((y) => <line key={y} x1="0" x2="760" y1={y} y2={y} className="tk-grid" />)}
        <path className="tk-area" d="M0 72 C20 82 30 47 48 60 S82 77 101 38 S128 62 149 45 S174 38 195 56 S224 88 241 64 S263 44 286 58 S315 75 338 40 S371 29 391 58 S423 66 445 47 S475 48 494 45 S530 34 553 22 S589 19 611 38 S640 40 661 48 S696 22 720 30 S744 38 760 48 L760 230 L0 230Z" />
        <path className="tk-line" d="M0 72 C20 82 30 47 48 60 S82 77 101 38 S128 62 149 45 S174 38 195 56 S224 88 241 64 S263 44 286 58 S315 75 338 40 S371 29 391 58 S423 66 445 47 S475 48 494 45 S530 34 553 22 S589 19 611 38 S640 40 661 48 S696 22 720 30 S744 38 760 48" />
      </svg>
      <div className="tk-chart-labels"><span>23/04</span><span>28/04</span><span>03/05</span><span>08/05</span><span>13/05</span><span>18/05</span><span>22/05</span></div>
      <div className="tk-tooltip"><small>18/05/2024</small><b><i /> Uptime: 96.3%</b></div>
    </div>
  )
}
