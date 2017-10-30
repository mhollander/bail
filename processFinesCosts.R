library(ggplot2)
library(dplyr)

# set the working directory to that of the script itself
this.dir <- dirname(parent.frame(2)$ofile)
setwd(this.dir)

# find and open our csv file
csvFile <- file.path("fcdata", "out.csv")
fcdata <- read.csv(csvFile, header=FALSE)
names(fcdata) <- c("First", "Last", "Address", "PaymentPlanNumber","Status", "DelinquentAmount", "TotalOwed", "MonthlyPayment")

#calculate some columns for later use
fcdata$MonthlyPaymentsBehind <- round(fcdata$DelinquentAmount / fcdata$MonthlyPayment, 3)
fcdata$DelinquentAmountPercentofTotalOwed <- round(fcdata$DelinquentAmount / fcdata$TotalOwed * 100,0)

dfStatus <- group_by(fcdata, Status)
dfMonthlyPaymentsBehind <- summarise(dfStatus[dfStatus$MonthlyPayment > 0,], MonthlyPaymentsBehind.mean=mean(MonthlyPaymentsBehind))

# start getting stats

# note that there are fewer people than entries since a person may appear multiple times. 
# to deal with that, counting total rows, total unique payment plan numbers, and total unique first, last name combos
totalEntires <- nrow(fcdata)
totalUniquePaymentPlans <- NROW(unique(fcdata$PaymentPlanNumber))
totalUniqueNames <- NROW(unique(fcdata[c("First","Last")]))
totalReferred <- NROW(fcdata[fcdata$Status == "Referred",])
totalActive <-  NROW(fcdata[fcdata$Status == "Active",])
totalOwed <- sum(fcdata$TotalOwed)
totalDelinquent <- sum(fcdata$DelinquentAmount)
totalOwedReferred <- sum(fcdata$TotalOwed[fcdata$Status=="Referred"])
totalDelinquentReferred <- sum(fcdata$DelinquentAmount[fcdata$Status=="Referred"])


# get stats about the money owed
statsDelAmt <- fivenum(fcdata$DelinquentAmount)
statsTotalOwed <- fivenum(fcdata$TotalOwed)
statsMonthlyPayment <- fivenum(fcdata$MonthlyPayment)
statsPaymentsBehind <- fivenum(fcdata$MonthlyPaymentsBehind)
statsDelinquentPercent <- fivenum(fcdata$DelinquentAmountPercentofTotalOwed)

#same for active and referred status
statsDelAmtReferred <- fivenum(fcdata$DelinquentAmount[fcdata$Status=="Referred"])
statsTotalOwedReferred <- fivenum(fcdata$TotalOwed[fcdata$Status=="Referred"])
statsMonthlyPaymentReferred <- fivenum(fcdata$MonthlyPayment[fcdata$Status=="Referred"])
statsPaymentsBehindReferred <- fivenum(fcdata$MonthlyPaymentsBehind[fcdata$Status=="Referred"])
statsDelinquentPercentReferred <- fivenum(fcdata$DelinquentAmountPercentofTotalOwed[fcdata$Status=="Referred"])

statsDelAmtActive <- fivenum(fcdata$DelinquentAmount[fcdata$Status=="Active"])
statsTotalOwedActive <- fivenum(fcdata$TotalOwed[fcdata$Status=="Active"])
statsMonthlyPaymentActive <- fivenum(fcdata$MonthlyPayment[fcdata$Status=="Active"])
statsPaymentsBehindActive <- fivenum(fcdata$MonthlyPaymentsBehind[fcdata$Status=="Active"])
statsDelinquentPercentActive <- fivenum(fcdata$DelinquentAmountPercentofTotalOwed[fcdata$Status=="Active"])


#histogram of monthly payments behind, categorized by status = Active or Referred.
# the histogram is liminted to people wiht a montly payment (295 people have no monthly payment) and people behind by
# less than 300 months (623 are behind by that much)
subfcdata <- fcdata[fcdata$MonthlyPayment > 0 & fcdata$MonthlyPaymentsBehind < 200,]
ggplot(subfcdata, aes(MonthlyPaymentsBehind, fill=Status)) + 
  geom_density(binwidth=3, alpha=.5, position="identity") +
  geom_vline(data=dfMonthlyPaymentsBehind, aes(xintercept=MonthlyPaymentsBehind.mean, colour=Status, ymin=0),
             linetype="dashed", size=2)+
  scale_x_continuous(breaks = sort(c(round(seq(0, 
                                         max(subfcdata$MonthlyPaymentsBehind), length.out=5),0),
                                     round(dfMonthlyPaymentsBehind$MonthlyPaymentsBehind.mean),0))) +
  coord_cartesian(ylim=c(0,.04))+
  theme_minimal()+
  theme(plot.title = element_text(face="bold", hjust=.5, size=15),
        legend.position="top",
        #legend.title = element_blank(),
        axis.title = element_text(size=12, face="bold"),
        x.axis = element_blank(),
        axis.text = element_text(size=12)
  ) +
  ggtitle("Distribution of # of Monthly Payments Delinquent by Payment Plan Status") +
  labs(x="Monthly Payments Behind", y=NULL;
       caption="Graph scaled to not include people behind by more than 200 payments (there are very few).\nData downloaded from http://courts.phila.gov/collections/index.asp on 10/27/2017")  
  

