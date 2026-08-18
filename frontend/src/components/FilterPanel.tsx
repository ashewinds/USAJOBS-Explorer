type FilterPanelProps = {
    selectedSeries: string;
    onSeriesChange: (series: string) => void;

    selectedStatus: string;
    onStatusChange: (status: string) => void;

    selectedSort: string;
    onSortChange: (sort: string) => void;

    selectedRemote: string;
    onRemoteChange: (remote: string) => void;
}

export default function FilterPanel({
    selectedSeries,
    onSeriesChange,
    selectedStatus,
    onStatusChange,
    selectedSort,
    onSortChange,
    selectedRemote,
    onRemoteChange
}: FilterPanelProps) {
    return (
        <div className="filter-panel">
            <label>
                Series: 
                <select
                    value={selectedSeries}
                    onChange={(e) => onSeriesChange(e.target.value)}
                >
                    <option value="0343">0343</option>
                    <option value="2210">2210</option>
                </select>
            </label>

            <label>
                Status: 
                <select
                    value={selectedStatus}
                    onChange={(e) => onStatusChange(e.target.value)}
                >
                    <option value="Open">Open</option>
                    <option value="Closed">Closed</option>
                </select>
            </label>

            <label>
                Sort: 
                    <select
                        value={selectedSort}
                        onChange={(e) => onSortChange(e.target.value)}
                    >
                        <option value="newest">Newest</option>
                        <option value="closing-soonest">Closing soon</option>
                        <option value="grade-asc">Pay Plan & Grade (Ascending)</option>
                        <option value="grade-desc">Pay Plan & Grade (Descending)</option>

                    </select>
            </label>

            <label>
                Remote: 
                    <select
                        value={selectedRemote}
                        onChange={(e) => onRemoteChange(e.target.value)}
                    >
                        <option value="all">ALL</option>
                        <option value="remote">Remote</option>
                        <option value="not-remote">Not Remote</option>
                    </select>
            </label>
        </div>
    );
}