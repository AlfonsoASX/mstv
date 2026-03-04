import { Users, MapPin, AlertTriangle, Clock } from "lucide-react"

export default function AdminDashboard() {
    const stats = [
        { label: "Active Guards", value: "12", icon: Users, color: "text-blue-600", bg: "bg-blue-100" },
        { label: "Active Sites", value: "5", icon: MapPin, color: "text-emerald-600", bg: "bg-emerald-100" },
        { label: "Open Incidents", value: "3", icon: AlertTriangle, color: "text-amber-600", bg: "bg-amber-100" },
        { label: "On Duty", value: "8", icon: Clock, color: "text-indigo-600", bg: "bg-indigo-100" },
    ]

    return (
        <div>
            <div className="mb-8">
                <h1 className="heading-2 mb-2">Dashboard</h1>
                <p className="text-muted">Overview of security operations.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {stats.map((stat) => {
                    const Icon = stat.icon
                    return (
                        <div key={stat.label} className="card flex items-center p-6">
                            <div className={`p-3 rounded-full ${stat.bg} mr-4`}>
                                <Icon className={`w-6 h-6 ${stat.color}`} />
                            </div>
                            <div>
                                <p className="text-sm text-muted font-medium">{stat.label}</p>
                                <h3 className="text-2xl font-bold">{stat.value}</h3>
                            </div>
                        </div>
                    )
                })}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="card">
                    <h3 className="heading-4 mb-4">Recent Activity</h3>
                    <div className="space-y-4">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="flex items-start pb-4 border-b border-slate-100 last:border-0 last:pb-0">
                                <div className="w-2 h-2 mt-2 rounded-full bg-indigo-500 mr-3" />
                                <div>
                                    <p className="text-sm font-medium">Guard Check-in at Main HQ</p>
                                    <p className="text-xs text-muted">2 minutes ago</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="card">
                    <h3 className="heading-4 mb-4">Pending Incidents</h3>
                    <div className="space-y-4">
                        {[1, 2].map((i) => (
                            <div key={i} className="flex items-start p-3 bg-amber-50 rounded-md border border-amber-100">
                                <AlertTriangle className="w-5 h-5 text-amber-600 mr-3 mt-0.5" />
                                <div>
                                    <p className="text-sm font-medium text-amber-900">Suspicious Activity Reported</p>
                                    <p className="text-xs text-amber-700 mt-1">Site: North Warehouse • Priority: High</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    )
}
