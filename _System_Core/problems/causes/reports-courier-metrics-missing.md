# Cause: Reports page had no courier-level shipment aggregation

Reports only exposed stock movement and department snapshot data, so operations could not see shipment mix by courier (J&T, SPX, Flash) in one analytics view. Courier strings also vary in source rows, so direct grouping without normalization would split equivalent couriers into multiple labels.
