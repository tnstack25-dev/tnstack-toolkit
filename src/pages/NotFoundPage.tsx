import { Link } from 'react-router-dom'

function NotFoundPage() {
  return (
    <article className="tk-card tk-empty-state">
      <h1>Không tìm thấy trang</h1>
      <p>Đường dẫn này không tồn tại trong TNStack Toolkit.</p>
      <Link to="/dashboard">Quay lại Dashboard</Link>
    </article>
  )
}

export default NotFoundPage
