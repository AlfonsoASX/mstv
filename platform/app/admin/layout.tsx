import { Sidebar } from "@/components/admin/Sidebar"
import { Topbar } from "@/components/admin/Topbar"

export default function AdminLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
            <Sidebar />
            <div className="pl-64">
                <Topbar />
                <main className="p-6">
                    <div className="container">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    )
}
