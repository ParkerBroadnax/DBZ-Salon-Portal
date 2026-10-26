#!/usr/bin/env python3
"""Summarize the daily export partitions written by bin/export-daily.php.

    python3 scripts/summarize_exports.py [exports_dir] [--days 7]

Reads exports/date=YYYY-MM-DD/bookings_by_service.csv for the most recent N
complete partitions (those with a _SUCCESS marker), checks basic data quality,
and prints bookings and booked value per service. Standard library only.
"""

from __future__ import annotations

import argparse
import csv
import sys
from collections import defaultdict
from decimal import Decimal
from pathlib import Path


def complete_partitions(root: Path) -> list[Path]:
    parts = sorted(p for p in root.glob("date=*") if p.is_dir())
    incomplete = [p.name for p in parts if not (p / "_SUCCESS").exists()]
    if incomplete:
        print(f"warning: skipping incomplete partitions: {', '.join(incomplete)}", file=sys.stderr)
    return [p for p in parts if (p / "_SUCCESS").exists()]


def check_row(row: dict[str, str], where: str) -> list[str]:
    problems = []
    if not row.get("service"):
        problems.append(f"{where}: empty service name")
    try:
        if int(row["bookings"]) < 0:
            problems.append(f"{where}: negative bookings")
    except (KeyError, ValueError):
        problems.append(f"{where}: bookings is not an integer")
    try:
        Decimal(row["booked_value"])
    except Exception:
        problems.append(f"{where}: booked_value is not a number")
    return problems


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("exports", nargs="?", default="exports", type=Path)
    parser.add_argument("--days", type=int, default=7, help="number of most recent partitions")
    args = parser.parse_args()

    parts = complete_partitions(args.exports)[-args.days:]
    if not parts:
        print(f"no complete partitions under {args.exports}", file=sys.stderr)
        return 1

    bookings: dict[str, int] = defaultdict(int)
    value: dict[str, Decimal] = defaultdict(Decimal)
    problems: list[str] = []

    for part in parts:
        with open(part / "bookings_by_service.csv", newline="") as fh:
            for i, row in enumerate(csv.DictReader(fh), start=2):
                row_problems = check_row(row, f"{part.name} line {i}")
                problems += row_problems
                if row_problems:
                    continue
                bookings[row["service"]] += int(row["bookings"])
                value[row["service"]] += Decimal(row["booked_value"])

    first, last = parts[0].name.split("=")[1], parts[-1].name.split("=")[1]
    print(f"Bookings {first} to {last} ({len(parts)} days)\n")
    print(f"{'Service':<22}{'Bookings':>10}{'Booked value':>16}")
    for service in sorted(bookings, key=lambda s: (-bookings[s], s)):
        print(f"{service:<22}{bookings[service]:>10}{value[service]:>16,.2f}")
    print(f"{'Total':<22}{sum(bookings.values()):>10}{sum(value.values()):>16,.2f}")

    if problems:
        print("\nData quality problems:", file=sys.stderr)
        for p in problems:
            print(f"  {p}", file=sys.stderr)
        return 3
    return 0


if __name__ == "__main__":
    sys.exit(main())
