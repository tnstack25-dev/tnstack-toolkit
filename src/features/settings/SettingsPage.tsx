import { Button, Spinner, TextControl, ToggleControl } from '@wordpress/components'
import { useEffect, useState } from 'react'
import { requestSettings } from './api'
import type { NoticeState, Settings } from './types'

function SettingsPage() {
  const [settings, setSettings] = useState<Settings>({ title: '', is_enabled: false })
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [notice, setNotice] = useState<NoticeState | null>(null)

  useEffect(() => {
    requestSettings()
      .then(setSettings)
      .catch((error: Error) => setNotice({ status: 'error', message: error.message }))
      .finally(() => setIsLoading(false))
  }, [])

  const saveSettings = async () => {
    setIsSaving(true)
    setNotice(null)
    try {
      setSettings(await requestSettings({ method: 'POST', body: JSON.stringify(settings) }))
      setNotice({ status: 'success', message: 'Đã lưu cài đặt.' })
    } catch (error) {
      setNotice({ status: 'error', message: error instanceof Error ? error.message : 'Không thể lưu cài đặt.' })
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <article className="tk-card tk-settings-card">
        <h2>Cài đặt chung</h2>
        <p>Thiết lập thông tin và tính năng của plugin.</p>
        {notice && <div className={`tk-form-notice ${notice.status}`}>{notice.message}</div>}
        {isLoading ? <Spinner /> : <>
          <TextControl label="Tiêu đề" value={settings.title} onChange={(title) => setSettings({ ...settings, title })} />
          <ToggleControl label="Bật tính năng X" checked={settings.is_enabled} onChange={(is_enabled) => setSettings({ ...settings, is_enabled })} />
          <Button variant="primary" isBusy={isSaving} disabled={isSaving} onClick={saveSettings}>Lưu cài đặt</Button>
        </>}
    </article>
  )
}

export default SettingsPage
