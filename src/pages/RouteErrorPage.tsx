import { Link, isRouteErrorResponse, useRouteError } from 'react-router-dom'

function RouteErrorPage() {
  const error = useRouteError()
  const message = isRouteErrorResponse(error) ? `${error.status} ${error.statusText}` : 'Không thể tải trang này.'

  return (
    <article className="tk-card tk-empty-state">
      <h1>Đã xảy ra lỗi</h1>
      <p>{message}</p>
      <Link to="/dashboard">Quay lại Dashboard</Link>
    </article>
  )
}

export default RouteErrorPage
