"use client"

import { useFormState, useFormStatus } from "react-dom"
import { createSite } from "@/lib/actions/sites"
import { Loader2 } from "lucide-react"

const initialState = {
    message: "",
    errors: {},
}

function SubmitButton() {
    const { pending } = useFormStatus()

    return (
        <button
            type="submit"
            disabled={pending}
            className="btn btn-primary w-full"
        >
            {pending ? <Loader2 className="animate-spin mr-2 h-4 w-4" /> : null}
            Create Site
        </button>
    )
}

export function CreateSiteForm() {
    const [state, dispatch] = useFormState(createSite, initialState)

    return (
        <form action={dispatch} className="space-y-4">
            <div>
                <label className="label" htmlFor="name">Site Name</label>
                <input id="name" name="name" type="text" className="input" placeholder="e.g. Main HQ" required />
                {state?.errors?.name && <p className="text-red-500 text-xs mt-1">{state.errors.name}</p>}
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="label" htmlFor="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="number" step="any" className="input" placeholder="e.g. 19.4326" required />
                    {state?.errors?.latitude && <p className="text-red-500 text-xs mt-1">{state.errors.latitude}</p>}
                </div>
                <div>
                    <label className="label" htmlFor="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="number" step="any" className="input" placeholder="e.g. -99.1332" required />
                    {state?.errors?.longitude && <p className="text-red-500 text-xs mt-1">{state.errors.longitude}</p>}
                </div>
            </div>

            <div>
                <label className="label" htmlFor="geofenceRadius">Geofence Radius (meters)</label>
                <input id="geofenceRadius" name="geofenceRadius" type="number" className="input" defaultValue={50} min={10} required />
                {state?.errors?.geofenceRadius && <p className="text-red-500 text-xs mt-1">{state.errors.geofenceRadius}</p>}
            </div>

            {state?.message && (
                <p className="text-red-600 text-sm">
                    {state.message}
                </p>
            )}

            <SubmitButton />
        </form>
    )
}
